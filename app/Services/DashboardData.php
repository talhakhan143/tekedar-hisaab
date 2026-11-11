<?php

namespace App\Services;

use App\Models\ClientPayment;
use App\Models\GeneralOverhead;
use App\Models\MaterialPurchase;
use App\Models\OtherExpense;
use App\Models\Project;
use App\Models\RetentionRelease;
use App\Models\Vendor;
use App\Models\WagePayment;
use App\Models\WorkEntry;
use App\Models\Worker;
use App\Models\WorkerAdvance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * All-company aggregates for the dashboard. Integer paisa throughout.
 */
class DashboardData
{
    /**
     * Bill me se sirf maal ki qeemat. Bilkul wahi defensive expression jo
     * ProjectFinance me hai (wahan private hai), aur jaan boojh kar wahi:
     * dashboard ka pie aur project ka pie ek hi tareeqe se banna chahiye,
     * warna dono alag kahani sunate hain. COALESCE is liye ke NULL goods wali
     * row SUM() se gir kar apni poori lagat gum na kar de.
     */
    private const GOODS_EXPR = 'COALESCE(goods_amount_paisa, amount_paisa - COALESCE(freight_paisa,0) - COALESCE(labour_paisa,0) - COALESCE(other_charges_paisa,0))';

    // ---------- Totals ----------
    public function totalBilledPaisa(): int { return (int) ClientPayment::sum('gross_amount_paisa'); }
    public function netReceivedPaisa(): int { return (int) ClientPayment::sum('net_received_paisa'); }
    public function retentionHeldPaisa(): int { return (int) ClientPayment::sum('retention_held_paisa'); }
    public function retentionReleasedPaisa(): int { return (int) RetentionRelease::sum('amount_paisa'); }
    public function retentionOutstandingPaisa(): int { return $this->retentionHeldPaisa() - $this->retentionReleasedPaisa(); }

    /**
     * Saari purchases par laga kiraya / mazdoori / deegar, ek hi query me.
     * Vendor ki qism ka filter jaan boojh kar nahi hai, warna kuch charges
     * kisi khane me hi na aate.
     */
    public function purchaseChargesPaisa(): array
    {
        $row = MaterialPurchase::selectRaw('COALESCE(SUM(freight_paisa),0) as freight_total, COALESCE(SUM(labour_paisa),0) as labour_total, COALESCE(SUM(other_charges_paisa),0) as other_total')
            ->first();

        return [
            'freight' => (int) ($row->freight_total ?? 0),
            'labour'  => (int) ($row->labour_total ?? 0),
            'other'   => (int) ($row->other_total ?? 0),
        ];
    }

    /**
     * Global taqseem bhi per-project taqseem jaisi hi hai: material me sirf
     * maal, purchase ki mazdoori labour me, aur kiraya + deegar "Other" me.
     *
     * Khayal rahe: material ko goods par le aana aur charges ko kisi khane me
     * wapas na daalna = totalProjectCostPaisa() utna hi ghat jata jitne saare
     * charges hain, aur accrued profit usi raqam se jhoota barh jata. Is liye
     * teenon charges neeche labour aur other me wapas ginay jate hain, aur jor
     * bilkul wahi rehta hai: Σ amount_paisa + wages + other expenses.
     */
    public function materialCostPaisa(): int { return (int) MaterialPurchase::sum(DB::raw(self::GOODS_EXPR)); }

    public function labourCostPaisa(): int
    {
        return (int) WorkEntry::sum('computed_wage_paisa') + $this->purchaseChargesPaisa()['labour'];
    }

    public function otherCostPaisa(): int
    {
        $charges = $this->purchaseChargesPaisa();
        return (int) OtherExpense::sum('amount_paisa') + $charges['freight'] + $charges['other'];
    }

    public function overheadPaisa(): int { return (int) GeneralOverhead::sum('amount_paisa'); }

    public function totalProjectCostPaisa(): int
    {
        return $this->materialCostPaisa() + $this->labourCostPaisa() + $this->otherCostPaisa();
    }

    /** Cash actually paid out (excludes overheads, added separately where needed). */
    public function cashOutPaisa(): int
    {
        $matPaid = (int) MaterialPurchase::sum('amount_paid_paisa');
        $wages   = (int) WagePayment::sum('amount_paisa');
        $advNet  = (int) WorkerAdvance::where('type', 'advance_given')->sum('amount_paisa')
                 - (int) WorkerAdvance::where('type', 'recovery')->sum('amount_paisa');
        $other   = (int) OtherExpense::sum('amount_paisa');
        return $matPaid + $wages + $advNet + $other;
    }

    // ---------- Profit (overheads reduce OVERALL profit) ----------
    public function accruedProfitPaisa(): int
    {
        return $this->totalBilledPaisa() - $this->totalProjectCostPaisa() - $this->overheadPaisa();
    }

    public function cashProfitPaisa(): int
    {
        return ($this->netReceivedPaisa() + $this->retentionReleasedPaisa()) - $this->cashOutPaisa() - $this->overheadPaisa();
    }

    // ---------- Outstanding ----------
    public function vendorPayablePaisa(): int
    {
        return (int) Vendor::sum('opening_balance_paisa') + (int) MaterialPurchase::sum('balance_due_paisa');
    }

    public function workerAdvancesOutstandingPaisa(): int
    {
        $given = (int) WorkerAdvance::where('type', 'advance_given')->sum('amount_paisa');
        $recov = (int) WorkerAdvance::where('type', 'recovery')->sum('amount_paisa');
        return max(0, $given - $recov);
    }

    /** Days since earliest retention-bearing payment that is still (net) outstanding. */
    public function retentionAgingDays(): ?int
    {
        if ($this->retentionOutstandingPaisa() <= 0) {
            return null;
        }
        $earliest = ClientPayment::where('retention_held_paisa', '>', 0)->min('date');
        return $earliest ? Carbon::parse($earliest)->diffInDays(now()) : null;
    }

    // ---------- Lena / Dena (overall) ----------
    /** Total receivable from clients = Σ (contract − billed), only positive. */
    public function totalReceivablePaisa(): int
    {
        $sum = 0;
        foreach (Project::with('clientPayments')->get() as $p) {
            $billed = (int) $p->clientPayments->sum('gross_amount_paisa');
            $sum += max(0, (int) $p->contract_value_paisa - $billed);
        }
        return $sum;
    }

    /** Total payable = vendor udhaar + Σ worker dues (positive). */
    public function totalPayablePaisa(): int
    {
        $workers = 0;
        foreach (Worker::all() as $w) {
            $workers += max(0, $w->payablePaisa());
        }
        return $this->vendorPayablePaisa() + $workers;
    }

    // ---------- This month ----------
    public function monthReceivedPaisa(?Carbon $month = null): int
    {
        $m = $month ?? now();
        return (int) ClientPayment::whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('net_received_paisa');
    }

    public function monthSpentPaisa(?Carbon $month = null): int
    {
        $m = $month ?? now();
        $matPaid = (int) MaterialPurchase::whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('amount_paid_paisa');
        $wages   = (int) WagePayment::whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('amount_paisa');
        $advNet  = (int) WorkerAdvance::where('type', 'advance_given')->whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('amount_paisa')
                 - (int) WorkerAdvance::where('type', 'recovery')->whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('amount_paisa');
        $other   = (int) OtherExpense::whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('amount_paisa');
        $overhead= (int) GeneralOverhead::where('month', $m->format('Y-m'))->sum('amount_paisa');
        return $matPaid + $wages + $advNet + $other + $overhead;
    }

    public function monthNetProfitPaisa(?Carbon $month = null): int
    {
        return $this->monthReceivedPaisa($month) - $this->monthSpentPaisa($month);
    }

    // ---------- Charts ----------
    /** Last 12 months profit trend (received − spent). Returns [labels[], data[] (rupees)]. */
    public function profitTrend(): array
    {
        $labels = [];
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->copy()->subMonths($i);
            $labels[] = $m->format('M y');
            $data[] = round($this->monthNetProfitPaisa($m) / 100, 2);
        }
        return ['labels' => $labels, 'data' => $data];
    }

    /** Cost pie across all projects (rupees). */
    public function costPie(): array
    {
        return [
            'labels' => ['Material', 'Labour', 'Other'],
            'data'   => [
                round($this->materialCostPaisa() / 100, 2),
                round($this->labourCostPaisa() / 100, 2),
                round($this->otherCostPaisa() / 100, 2),
            ],
        ];
    }

    // ---------- Project rankings ----------
    public function activeProjectsCount(): int { return Project::where('status', 'active')->count(); }
    public function totalContractValuePaisa(): int { return (int) Project::sum('contract_value_paisa'); }

    /** Each project's projected profit; used for top-5 and loss list. */
    public function projectProfits(): \Illuminate\Support\Collection
    {
        return Project::all()->map(function (Project $p) {
            $f = ProjectFinance::for($p);
            return [
                'project'   => $p,
                'projected' => $f->projectedProfitPaisa(),
                'cost'      => $f->totalAccruedCostPaisa(),
                'isLoss'    => $f->isLoss(),
            ];
        })->sortByDesc('projected')->values();
    }
}
