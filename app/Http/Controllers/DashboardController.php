<?php

namespace App\Http\Controllers;

use App\Services\DashboardData;

class DashboardController extends Controller
{
    public function index(DashboardData $d)
    {
        $profits = $d->projectProfits();

        return view('dashboard', [
            'activeProjects'       => $d->activeProjectsCount(),
            'contractValue'        => $d->totalContractValuePaisa(),
            'monthReceived'        => $d->monthReceivedPaisa(),
            'monthSpent'           => $d->monthSpentPaisa(),
            'monthNetProfit'       => $d->monthNetProfitPaisa(),
            'totalBilled'          => $d->totalBilledPaisa(),
            'totalCost'            => $d->totalProjectCostPaisa() + $d->overheadPaisa(),
            'accruedProfit'        => $d->accruedProfitPaisa(),
            'cashProfit'           => $d->cashProfitPaisa(),
            'retentionOutstanding' => $d->retentionOutstandingPaisa(),
            'retentionAging'       => $d->retentionAgingDays(),
            'vendorPayables'       => $d->vendorPayablePaisa(),
            'workerAdvances'       => $d->workerAdvancesOutstandingPaisa(),
            'profitTrend'          => $d->profitTrend(),
            'costPie'              => $d->costPie(),
            'topProjects'          => $profits->take(5),
            'lossProjects'         => $profits->filter(fn ($r) => $r['isLoss'])->values(),
        ]);
    }
}
