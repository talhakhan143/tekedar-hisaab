<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\MaterialPurchase;
use App\Models\OtherExpense;
use App\Models\Project;
use App\Models\WagePayment;
use App\Models\Worker;
use App\Models\WorkEntry;
use App\Models\WorkerAdvance;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Printable vouchers / invoices for every money transaction the app records:
 * supplier purchase invoices, labour wage vouchers, worker advance (peshgi)
 * vouchers, and client payment receipts. Each renders a standalone, print-ready
 * page (browser Print → Save as PDF) on the company letterhead.
 */
class VoucherController extends Controller
{
    /** Supplier / vendor purchase invoice. */
    public function material(MaterialPurchase $materialPurchase): View
    {
        $materialPurchase->load(['vendor', 'project']);

        return view('vouchers.material', ['purchase' => $materialPurchase]);
    }

    /** Labour wage payment voucher. */
    public function wage(WagePayment $wagePayment): View
    {
        $wagePayment->load(['worker', 'project']);

        return view('vouchers.wage', ['payment' => $wagePayment]);
    }

    /** Worker advance (peshgi) / recovery voucher. */
    public function advance(WorkerAdvance $workerAdvance): View
    {
        $workerAdvance->load(['worker', 'project']);

        return view('vouchers.advance', ['advance' => $workerAdvance]);
    }

    /** Client payment receipt (money in). */
    public function clientPayment(ClientPayment $clientPayment): View
    {
        $clientPayment->load('project');

        return view('vouchers.client', ['payment' => $clientPayment]);
    }

    /** Other expense voucher (fuel, transport, rent, etc.). */
    public function expense(OtherExpense $otherExpense): View
    {
        $otherExpense->load('project');

        return view('vouchers.expense', ['expense' => $otherExpense]);
    }

    /**
     * Worker account statement — earned / paid / advances / payable. Scoped to
     * one project when ?project= is given, otherwise the worker's full ledger.
     */
    public function workerStatement(Request $request, Worker $worker): View
    {
        $project = $request->integer('project')
            ? Project::find($request->integer('project'))
            : null;

        $entries  = WorkEntry::where('worker_id', $worker->id)
            ->when($project, fn ($q) => $q->where('project_id', $project->id));
        $pays     = WagePayment::where('worker_id', $worker->id)
            ->when($project, fn ($q) => $q->where('project_id', $project->id));
        $advs     = WorkerAdvance::where('worker_id', $worker->id)
            ->when($project, fn ($q) => $q->where('project_id', $project->id));

        $earned   = (int) $entries->sum('computed_wage_paisa');
        $days     = (float) (clone $entries)->sum('days_present');
        $paid     = (int) $pays->sum('amount_paisa');
        $advGiven = (int) (clone $advs)->where('type', 'advance_given')->sum('amount_paisa');
        $advRecov = (int) (clone $advs)->where('type', 'recovery')->sum('amount_paisa');
        $advNet   = $advGiven - $advRecov;
        $payable  = $earned - $paid - $advNet;

        return view('vouchers.worker-statement', [
            'worker'   => $worker,
            'project'  => $project,
            'days'     => $days,
            'earned'   => $earned,
            'paid'     => $paid,
            'advNet'   => $advNet,
            'payable'  => $payable,
        ]);
    }
}
