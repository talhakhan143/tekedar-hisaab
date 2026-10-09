<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\GeneralOverhead;
use App\Models\MaterialPurchase;
use App\Models\OtherExpense;
use App\Models\Project;
use App\Models\Vendor;
use App\Models\WagePayment;
use App\Models\Worker;
use App\Models\WorkerAdvance;
use App\Services\ProjectFinance;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index', [
            'projects' => Project::orderBy('name')->get(),
            'thisMonth' => now()->format('Y-m'),
        ]);
    }

    // ---------- Monthly profit report ----------
    public function monthly(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$y, $m] = array_pad(explode('-', $month), 2, now()->month);
        $carbon = Carbon::createFromDate((int) $y, (int) $m, 1);

        $rows = Project::orderBy('name')->get()->map(function (Project $p) use ($y, $m) {
            $received = (int) $p->clientPayments()->whereYear('date', $y)->whereMonth('date', $m)->sum('net_received_paisa');
            $matPaid  = (int) $p->materialPurchases()->whereYear('date', $y)->whereMonth('date', $m)->sum('amount_paid_paisa');
            $wages    = (int) $p->wagePayments()->whereYear('date', $y)->whereMonth('date', $m)->sum('amount_paisa');
            $other    = (int) $p->otherExpenses()->whereYear('date', $y)->whereMonth('date', $m)->sum('amount_paisa');
            $spent    = $matPaid + $wages + $other;
            return ['name' => $p->name, 'received' => $received, 'spent' => $spent, 'profit' => $received - $spent];
        })->filter(fn ($r) => $r['received'] || $r['spent'])->values();

        $overheads = (int) GeneralOverhead::where('month', $month)->sum('amount_paisa');
        $totalReceived = $rows->sum('received');
        $totalSpent = $rows->sum('spent');
        $netProfit = $totalReceived - $totalSpent - $overheads;

        $data = compact('month', 'carbon', 'rows', 'overheads', 'totalReceived', 'totalSpent', 'netProfit');

        if ($request->get('export') === 'csv') {
            return $this->csv("monthly-profit-{$month}.csv", array_merge(
                [['Project', 'Received', 'Spent', 'Profit']],
                $rows->map(fn ($r) => [$r['name'], Money::toRupees($r['received']), Money::toRupees($r['spent']), Money::toRupees($r['profit'])])->all(),
                [['General Overheads', '', '', Money::toRupees(-$overheads)]],
                [['NET PROFIT', Money::toRupees($totalReceived), Money::toRupees($totalSpent), Money::toRupees($netProfit)]],
            ));
        }
        if ($request->get('export') === 'pdf') {
            return Pdf::loadView('reports.monthly_pdf', $data)->download("monthly-profit-{$month}.pdf");
        }

        return view('reports.monthly', $data);
    }

    // ---------- Outstanding report ----------
    public function outstanding(Request $request)
    {
        $receivables = Project::orderBy('name')->get()->map(function (Project $p) {
            $f = ProjectFinance::for($p);
            return ['name' => $p->name, 'receivable' => $f->receivablePaisa()];
        })->filter(fn ($r) => $r['receivable'] > 0)->values();

        $payables = Vendor::orderBy('name')->get()->map(fn (Vendor $v) => ['name' => $v->name, 'amount' => $v->payablePaisa()])
            ->filter(fn ($r) => $r['amount'] > 0)->values();

        $advances = Worker::orderBy('name')->get()->map(fn (Worker $w) => ['name' => $w->name, 'amount' => $w->advancesOutstandingPaisa()])
            ->filter(fn ($r) => $r['amount'] > 0)->values();

        $data = compact('receivables', 'payables', 'advances');

        if ($request->get('export') === 'csv') {
            $lines = [['OUTSTANDING REPORT', now()->format('d-m-Y')], [], ['Receivables (client)', 'Total Receivable']];
            foreach ($receivables as $r) $lines[] = [$r['name'], Money::toRupees($r['receivable'])];
            $lines[] = []; $lines[] = ['Payables (vendor)', 'Amount'];
            foreach ($payables as $r) $lines[] = [$r['name'], Money::toRupees($r['amount'])];
            $lines[] = []; $lines[] = ['Worker Advances', 'Amount'];
            foreach ($advances as $r) $lines[] = [$r['name'], Money::toRupees($r['amount'])];
            return $this->csv('outstanding-report.csv', $lines);
        }
        if ($request->get('export') === 'pdf') {
            return Pdf::loadView('reports.outstanding_pdf', $data)->download('outstanding-report.pdf');
        }

        return view('reports.outstanding', $data);
    }

    // ---------- Project closeout report ----------
    public function closeout(Request $request, Project $project)
    {
        $f = ProjectFinance::for($project);
        $est = $f->estimateByCategory();
        $act = $f->actualByCategory();
        $data = compact('project', 'f', 'est', 'act');

        if ($request->get('export') === 'pdf') {
            return Pdf::loadView('reports.closeout_pdf', $data)->download("closeout-{$project->id}.pdf");
        }

        return view('reports.closeout', $data);
    }

    /** Stream an array of rows as a CSV download. */
    private function csv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
