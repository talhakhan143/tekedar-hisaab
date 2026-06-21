<?php

namespace App\Services;

use App\Models\Project;

/**
 * Single source of P&L truth for one project. All values are integer paisa.
 *
 * Cost model:
 *   - ACCRUED cost = full liability incurred (purchase amount, earned wages, expenses)
 *   - CASH paid    = money actually out the door (amount_paid, wage_payments + net advances, expenses)
 *
 * Profit:
 *   - Accrued profit = total gross client billing − accrued cost
 *   - Cash profit    = (net received + retention released) − cash paid out
 */
class ProjectFinance
{
    /** Map an other_expenses.category to its estimate-category bucket. */
    public const EXPENSE_BUCKET = [
        'transport'        => 'transport',
        'fuel'             => 'transport',
        'equipment_rental' => 'equipment',
        'tools'            => 'equipment',
        'utility'          => 'utility',
        'rent'             => 'overhead',
        'permits_govt'     => 'overhead',
        'bank_charges'     => 'overhead',
        'misc'             => 'misc',
        'food_chai'        => 'misc',
    ];

    public function __construct(public Project $project)
    {
    }

    public static function for(Project $project): self
    {
        return new self($project);
    }

    // ---------- Billing (money IN) ----------
    public function grossBilledPaisa(): int
    {
        return (int) $this->project->clientPayments()->sum('gross_amount_paisa');
    }

    public function netReceivedPaisa(): int
    {
        return (int) $this->project->clientPayments()->sum('net_received_paisa');
    }

    public function retentionHeldPaisa(): int
    {
        return (int) $this->project->clientPayments()->sum('retention_held_paisa');
    }

    public function retentionReleasedPaisa(): int
    {
        return (int) $this->project->retentionReleases()->sum('amount_paisa');
    }

    public function retentionOutstandingPaisa(): int
    {
        return $this->retentionHeldPaisa() - $this->retentionReleasedPaisa();
    }

    public function receivablePaisa(): int
    {
        // Contract value still not billed + retention outstanding.
        $unbilled = max(0, (int) $this->project->contract_value_paisa - $this->grossBilledPaisa());
        return $unbilled + $this->retentionOutstandingPaisa();
    }

    // ---------- Costs (ACCRUED) ----------
    public function materialCostPaisa(): int
    {
        // Material = purchases with no vendor OR a supplier vendor (subcontractor handled separately).
        return (int) $this->project->materialPurchases()
            ->where(function ($q) {
                $q->whereNull('vendor_id')
                  ->orWhereHas('vendor', fn ($v) => $v->where('type', 'supplier'));
            })
            ->sum('amount_paisa');
    }

    public function subcontractorCostPaisa(): int
    {
        return (int) $this->project->materialPurchases()
            ->whereHas('vendor', fn ($q) => $q->where('type', 'subcontractor'))
            ->sum('amount_paisa');
    }

    public function labourCostPaisa(): int
    {
        return (int) $this->project->workEntries()->sum('computed_wage_paisa');
    }

    /** Other-expense actuals grouped into estimate buckets. */
    public function otherExpenseBuckets(): array
    {
        $rows = $this->project->otherExpenses()
            ->selectRaw('category, SUM(amount_paisa) as total')
            ->groupBy('category')->pluck('total', 'category');

        $buckets = ['transport' => 0, 'equipment' => 0, 'utility' => 0, 'overhead' => 0, 'misc' => 0];
        foreach ($rows as $cat => $total) {
            $bucket = self::EXPENSE_BUCKET[$cat] ?? 'misc';
            $buckets[$bucket] += (int) $total;
        }
        return $buckets;
    }

    /** Actual spend per estimate category. */
    public function actualByCategory(): array
    {
        $other = $this->otherExpenseBuckets();
        return [
            'material'      => $this->materialCostPaisa(),
            'labour'        => $this->labourCostPaisa(),
            'subcontractor' => $this->subcontractorCostPaisa(),
            'transport'     => $other['transport'],
            'equipment'     => $other['equipment'],
            'utility'       => $other['utility'],
            'overhead'      => $other['overhead'],
            'misc'          => $other['misc'],
        ];
    }

    /** Estimated amount per category (amount already includes wastage inflation at entry). */
    public function estimateByCategory(): array
    {
        $rows = $this->project->estimates()
            ->selectRaw('category, SUM(amount_paisa) as total')
            ->groupBy('category')->pluck('total', 'category');

        $cats = ['material', 'labour', 'subcontractor', 'transport', 'equipment', 'utility', 'overhead', 'misc'];
        $out = [];
        foreach ($cats as $c) {
            $out[$c] = (int) ($rows[$c] ?? 0);
        }
        return $out;
    }

    public function totalAccruedCostPaisa(): int
    {
        return array_sum($this->actualByCategory());
    }

    /** Simplified pie buckets: material (incl subcontractor) / labour / other. */
    public function costPie(): array
    {
        $a = $this->actualByCategory();
        return [
            'material' => $a['material'] + $a['subcontractor'],
            'labour'   => $a['labour'],
            'other'    => $a['transport'] + $a['equipment'] + $a['utility'] + $a['overhead'] + $a['misc'],
        ];
    }

    // ---------- Cash paid out ----------
    public function materialCashPaidPaisa(): int
    {
        return (int) $this->project->materialPurchases()->sum('amount_paid_paisa');
    }

    public function labourCashPaidPaisa(): int
    {
        $wages = (int) $this->project->wagePayments()->sum('amount_paisa');
        $given = (int) $this->project->workerAdvances()->where('type', 'advance_given')->sum('amount_paisa');
        $recov = (int) $this->project->workerAdvances()->where('type', 'recovery')->sum('amount_paisa');
        return $wages + $given - $recov;
    }

    public function otherCashPaidPaisa(): int
    {
        return (int) $this->project->otherExpenses()->sum('amount_paisa');
    }

    public function totalCashPaidPaisa(): int
    {
        return $this->materialCashPaidPaisa() + $this->labourCashPaidPaisa() + $this->otherCashPaidPaisa();
    }

    public function vendorPayablePaisa(): int
    {
        return (int) $this->project->materialPurchases()->sum('balance_due_paisa');
    }

    // ---------- Profit ----------
    public function accruedProfitPaisa(): int
    {
        return $this->grossBilledPaisa() - $this->totalAccruedCostPaisa();
    }

    /** Projected profit = full contract value − accrued cost so far. */
    public function projectedProfitPaisa(): int
    {
        return (int) $this->project->contract_value_paisa - $this->totalAccruedCostPaisa();
    }

    public function cashProfitPaisa(): int
    {
        return ($this->netReceivedPaisa() + $this->retentionReleasedPaisa()) - $this->totalCashPaidPaisa();
    }

    public function isLoss(): bool
    {
        return $this->projectedProfitPaisa() < 0;
    }

    // ---------- Per-sqft margin ----------
    public function actualCostPerSqftPaisa(): ?int
    {
        $area = (float) $this->project->covered_area_sqft;
        if ($area <= 0) {
            return null;
        }
        return (int) round($this->totalAccruedCostPaisa() / $area);
    }

    public function marginPerSqftPaisa(): ?int
    {
        if ($this->project->pricing_mode !== 'per_sqft' || ! $this->project->rate_per_sqft_paisa) {
            return null;
        }
        $cost = $this->actualCostPerSqftPaisa();
        if ($cost === null) {
            return null;
        }
        return (int) $this->project->rate_per_sqft_paisa - $cost;
    }
}
