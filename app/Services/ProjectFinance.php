<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

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

    /**
     * Bill me se sirf maal ki qeemat. Seedha goods_amount_paisa parhna kaafi
     * hota, magar jaan boojh kar COALESCE lagaya hai: agar kisi row me goods
     * NULL reh jaye (migration ka backfill na chala ho, ya kisi ne raw SQL se
     * row daal di ho) to SUM() usay chup chaap chhod deti aur us purchase ki
     * poori lagat ghayab ho kar munafa jhoota barha deti. Aise waqt bill me se
     * charges nikal kar maal ki qeemat khud bana lete hain.
     */
    private const GOODS_EXPR = 'COALESCE(goods_amount_paisa, amount_paisa - COALESCE(freight_paisa,0) - COALESCE(labour_paisa,0) - COALESCE(other_charges_paisa,0))';

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
    /**
     * Material = har wo purchase jo sub-contractor ki nahi. Filter jaan boojh
     * kar sub-contractor wale filter ka theek ulta hai.
     *
     * Pehle yahan "vendor_id NULL ya vendor type=supplier" likha tha. Vendor
     * SoftDeletes istemal karta hai, is liye jis purchase ka vendor delete kar
     * diya gaya ho wo na material me aati thi na sub-contractor me: us ki lagat
     * chup chaap gum ho jati thi jab ke us ka udhaar poora ginta rehta tha.
     * Ab material ∪ subcontractor = saari purchases, har ek theek ek dafa.
     *
     * Jor sirf maal ka hai, kiraya/mazdoori/deegar actualByCategory() me apne
     * apne khanon me jate hain, warna dohra ginti ho jati.
     */
    public function materialCostPaisa(): int
    {
        return (int) $this->project->materialPurchases()
            ->whereDoesntHave('vendor', fn ($q) => $q->where('type', 'subcontractor'))
            ->sum(DB::raw(self::GOODS_EXPR));
    }

    public function subcontractorCostPaisa(): int
    {
        return (int) $this->project->materialPurchases()
            ->whereHas('vendor', fn ($q) => $q->where('type', 'subcontractor'))
            ->sum(DB::raw(self::GOODS_EXPR));
    }

    /**
     * Purchases par laga kiraya / mazdoori / deegar, ek hi query me.
     *
     * Yahan vendor ki qism ka koi filter nahi, aur ye jaan boojh kar hai:
     * material aur subcontractor ki taqseem sirf maal par lagti hai. Agar
     * charges par bhi wahi filter laga dete to taqseem toot jati aur kuch
     * charges kisi khane me na aate.
     */
    public function purchaseChargesPaisa(): array
    {
        $row = $this->project->materialPurchases()
            ->selectRaw('COALESCE(SUM(freight_paisa),0) as freight_total, COALESCE(SUM(labour_paisa),0) as labour_total, COALESCE(SUM(other_charges_paisa),0) as other_total')
            ->first();

        return [
            'freight' => (int) ($row->freight_total ?? 0),
            'labour'  => (int) ($row->labour_total ?? 0),
            'other'   => (int) ($row->other_total ?? 0),
        ];
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

    /**
     * Actual spend per estimate category.
     *
     * Purchase ke charges yahan apne apne khanon me ghulte hain: kiraya
     * transport me, mazdoori labour me, deegar misc me. Jor ab bhi bilkul
     * utna hi hai jitna pehle tha, yani Σ amount_paisa, bas taqseem sach bolti
     * hai: material me sirf maal ki qeemat aati hai.
     */
    public function actualByCategory(): array
    {
        $other   = $this->otherExpenseBuckets();
        $charges = $this->purchaseChargesPaisa();

        return [
            'material'      => $this->materialCostPaisa(),
            'labour'        => $this->labourCostPaisa() + $charges['labour'],
            'subcontractor' => $this->subcontractorCostPaisa(),
            'transport'     => $other['transport'] + $charges['freight'],
            'equipment'     => $other['equipment'],
            'utility'       => $other['utility'],
            'overhead'      => $other['overhead'],
            'misc'          => $other['misc'] + $charges['other'],
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
        return array_sum($this->actualByCategory()) + $this->allocatedOverheadPaisa();
    }

    /**
     * Pro-rata share of general overheads, only when the settings toggle is ON.
     * Distributed across ACTIVE projects by contract value. Default OFF -> 0.
     */
    public function allocatedOverheadPaisa(): int
    {
        if (\App\Models\Setting::get('allocate_overheads') !== '1') {
            return 0;
        }
        if ($this->project->status !== 'active') {
            return 0;
        }
        $totalActive = (int) \App\Models\Project::where('status', 'active')->sum('contract_value_paisa');
        if ($totalActive <= 0) {
            return 0;
        }
        $overheads = (int) \App\Models\GeneralOverhead::sum('amount_paisa');
        return (int) round($overheads * (int) $this->project->contract_value_paisa / $totalActive);
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
