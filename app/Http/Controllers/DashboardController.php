<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Dashboard landing. Aggregates are placeholders for now and get wired to
     * real queries in Step 9, once the financial tables and models exist.
     */
    public function index()
    {
        return view('dashboard', [
            'activeProjects'       => 0,
            'contractValue'        => 0,
            'monthNetProfit'       => 0,
            'retentionOutstanding' => 0,
            'totalBilled'          => 0,
            'totalCost'            => 0,
            'accruedProfit'        => 0,
            'cashProfit'           => 0,
            'vendorPayables'       => 0,
            'workerAdvances'       => 0,
        ]);
    }
}
