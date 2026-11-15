<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\GeneralOverhead;
use App\Models\MaterialPurchase;
use App\Models\OtherExpense;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\WagePayment;
use App\Models\WorkEntry;
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
            'workers' => Worker::orderBy('name')->get(['id', 'name', 'role']),
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

    // ---------- Mazdoor ka hisaab, saare projects ----------
    /**
     * Ek banda chuno (ya saare), aur dekho kis project me kitna banta tha,
     * kitna diya, kitni peshgi gayi aur kitna baqi hai, saath me kul jama.
     *
     * Hisaab bilkul wahi hai jo Worker::duesByProjectFor() lagata hai
     * (banta tha − diya − (peshgi − wapsi)), is liye is page ka baqi aur
     * ledger/validation ka baqi hamesha ek hi number hota hai.
     */
    public function labour(Request $request)
    {
        $roles = Setting::workerRoles();

        $workerParam = trim((string) $request->get('worker', ''));
        $roleParam   = trim((string) $request->get('role', ''));
        $oneWorker   = ($workerParam !== '' && $workerParam !== 'all') ? (int) $workerParam : null;

        // Checkbox ka default ON hai: form hidden 0 + checkbox 1 bhejta hai, is
        // liye param aaya ho to usi ki baat mano. Bilkul na aaya ho (pehli dafa
        // page khula) to saare projects, kyunki malik ko yehi view chahiye.
        $allProjects = $request->has('all_projects') ? $request->boolean('all_projects') : true;
        $projectId   = $allProjects ? null : ((int) $request->get('project') ?: null);
        if (! $allProjects && $projectId === null) {
            $allProjects = true; // project chuna hi nahi, khali page dene se behtar sab dikha do
        }

        $projectNames = Project::orderBy('name')->pluck('name', 'id');
        $allWorkers   = Worker::orderBy('name')->get(['id', 'name', 'role']);

        // Role filter sirf tab lagta hai jab koi khaas banda na chuna gaya ho,
        // warna ulte filter (mistri + mazdoor ka naam) mil kar khali report
        // bana dete hain aur malik ko lagta hai hisaab gum ho gaya.
        $workers = Worker::orderBy('name')
            ->when($oneWorker !== null, fn ($q) => $q->where('id', $oneWorker))
            ->when($oneWorker === null && $roleParam !== '' && $roleParam !== 'all', fn ($q) => $q->where('role', $roleParam))
            ->get();

        $buckets = $this->labourBuckets($workers->pluck('id')->all(), $projectId);

        $report = [];
        $grand  = ['days' => 0.0, 'earned' => 0, 'paid' => 0, 'advnet' => 0, 'baqi' => 0];

        foreach ($workers as $w) {
            $wb = $buckets[$w->id] ?? [];

            // Project ke naam ke hisaab se tarteeb, "bina project" wala bucket akhir me.
            $keys = array_keys($wb);
            usort($keys, function ($a, $b) use ($projectNames) {
                if ($a === 0 || $b === 0) {
                    return $a === 0 ? 1 : -1;
                }
                return strcasecmp((string) ($projectNames[$a] ?? ''), (string) ($projectNames[$b] ?? ''));
            });

            $lines = [];
            $total = ['days' => 0.0, 'earned' => 0, 'paid' => 0, 'advnet' => 0, 'baqi' => 0];

            foreach ($keys as $k) {
                $b    = $wb[$k];
                $baqi = $b['earned'] - $b['paid'] - $b['advnet'];

                $lines[] = [
                    'project_id' => $k,
                    'project'    => $k === 0 ? 'Bina project' : ($projectNames[$k] ?? 'Project #' . $k),
                    'days'       => $b['days'],
                    'earned'     => $b['earned'],
                    'paid'       => $b['paid'],
                    'advnet'     => $b['advnet'],
                    'baqi'       => $baqi,
                ];

                $total['days']   += $b['days'];
                $total['earned'] += $b['earned'];
                $total['paid']   += $b['paid'];
                $total['advnet'] += $b['advnet'];
                $total['baqi']   += $baqi;
            }

            $report[] = ['worker' => $w, 'lines' => $lines, 'total' => $total];

            foreach ($grand as $f => $v) {
                $grand[$f] = $v + $total[$f];
            }
        }

        // Dena aur advance alag alag bhi, kyunki do bandon ka ulta hisaab aapas
        // me cancel nahi hota: ek se lena hai, doosre ko dena hai.
        $grand['dena']    = array_sum(array_map(fn ($r) => max(0, $r['total']['baqi']), $report));
        $grand['advance'] = array_sum(array_map(fn ($r) => max(0, -$r['total']['baqi']), $report));

        $scopeLabel = ($oneWorker !== null && $workers->count() === 1 ? $workers->first()->name : 'Saare mazdoor')
            . ' · ' . ($allProjects ? 'Saare projects' : ($projectNames[$projectId] ?? 'Project #' . $projectId));

        $filters = ['all_projects' => $allProjects ? 1 : 0];
        if ($oneWorker !== null) {
            $filters['worker'] = $oneWorker;
        }
        if ($roleParam !== '' && $roleParam !== 'all') {
            $filters['role'] = $roleParam;
        }
        if (! $allProjects && $projectId !== null) {
            $filters['project'] = $projectId;
        }

        $data = compact(
            'report', 'grand', 'roles', 'projectNames', 'allWorkers',
            'allProjects', 'projectId', 'oneWorker', 'roleParam', 'scopeLabel', 'filters'
        );

        if ($request->get('export') === 'csv') {
            $lines = [
                ['MAZDOOR KA HISAAB'],
                [$scopeLabel, 'generated ' . now()->format('d-m-Y')],
                [],
                ['Worker', 'Kaam', 'Project', 'Din', 'Banta tha', 'Diya', 'Peshgi', 'Baqi'],
            ];
            foreach ($report as $r) {
                foreach ($r['lines'] as $l) {
                    $lines[] = [
                        $r['worker']->name, $r['worker']->role, $l['project'], $l['days'],
                        Money::toRupees($l['earned']), Money::toRupees($l['paid']),
                        Money::toRupees($l['advnet']), Money::toRupees($l['baqi']),
                    ];
                }
                $lines[] = [
                    $r['worker']->name, $r['worker']->role, 'TOTAL', $r['total']['days'],
                    Money::toRupees($r['total']['earned']), Money::toRupees($r['total']['paid']),
                    Money::toRupees($r['total']['advnet']), Money::toRupees($r['total']['baqi']),
                ];
                $lines[] = [];
            }
            $lines[] = [
                'GRAND TOTAL', '', '', $grand['days'],
                Money::toRupees($grand['earned']), Money::toRupees($grand['paid']),
                Money::toRupees($grand['advnet']), Money::toRupees($grand['baqi']),
            ];
            $lines[] = ['Lena (advance)', '', '', '', '', '', '', Money::toRupees($grand['advance'])];
            $lines[] = ['Dena (baqi)', '', '', '', '', '', '', Money::toRupees($grand['dena'])];

            return $this->csv('mazdoor-hisaab.csv', $lines);
        }
        if ($request->get('export') === 'pdf') {
            return Pdf::loadView('reports.labour_pdf', $data)->download('mazdoor-hisaab.pdf');
        }

        return view('reports.labour', $data);
    }

    /**
     * Har chune hue worker ka hisaab, project ke hisaab se alag alag.
     *
     * Sirf teen aggregate queries chalti hain, chahe saare workers chune hon,
     * is liye page worker × project ki ginti par slow nahi hota. Key 0 ka
     * matlab "bina project" hai, bilkul Worker::duesByProjectFor() ki tarah.
     *
     * @param  array<int>  $workerIds
     * @param  int|null  $projectId  null = saare projects
     * @return array<int, array<int, array{days: float, earned: int, paid: int, advnet: int}>>
     */
    private function labourBuckets(array $workerIds, ?int $projectId = null): array
    {
        $out = [];

        if ($workerIds === []) {
            return $out;
        }

        $scope = fn ($q) => $q->whereIn('worker_id', $workerIds)
            ->when($projectId !== null, fn ($qq) => $qq->where('project_id', $projectId))
            ->groupBy('worker_id', 'project_id');

        $cell = function (int $workerId, $projectKey) use (&$out): array {
            $k = (int) ($projectKey ?? 0);
            $out[$workerId][$k] ??= ['days' => 0.0, 'earned' => 0, 'paid' => 0, 'advnet' => 0];
            return [$workerId, $k];
        };

        // Banta tha + din (haazri)
        $rows = $scope(WorkEntry::query())
            ->selectRaw('worker_id, project_id, SUM(days_present) as days, SUM(computed_wage_paisa) as earned')
            ->get();
        foreach ($rows as $row) {
            [$w, $k] = $cell((int) $row->worker_id, $row->project_id);
            $out[$w][$k]['days']   += (float) $row->days;
            $out[$w][$k]['earned'] += (int) $row->earned;
        }

        // Diya (mazdoori ki adaygi)
        $rows = $scope(WagePayment::query())
            ->selectRaw('worker_id, project_id, SUM(amount_paisa) as paid')
            ->get();
        foreach ($rows as $row) {
            [$w, $k] = $cell((int) $row->worker_id, $row->project_id);
            $out[$w][$k]['paid'] += (int) $row->paid;
        }

        // Peshgi net = di hui peshgi − wapas kati hui
        $rows = $scope(WorkerAdvance::query())
            ->selectRaw("worker_id, project_id, SUM(CASE WHEN type = 'advance_given' THEN amount_paisa ELSE 0 END) as given, SUM(CASE WHEN type = 'recovery' THEN amount_paisa ELSE 0 END) as recovered")
            ->get();
        foreach ($rows as $row) {
            [$w, $k] = $cell((int) $row->worker_id, $row->project_id);
            $out[$w][$k]['advnet'] += (int) $row->given - (int) $row->recovered;
        }

        return $out;
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
