<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Services\ProjectFinance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $rows = Project::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$term}%")->orWhere('client_name', 'like', "%{$term}%")))
            ->orderByDesc('id')
            ->paginate(15)->withQueryString();

        // Attach lightweight P&L for the current page only.
        $rows->through(function (Project $p) {
            $f = ProjectFinance::for($p);
            return [
                'project'   => $p,
                'cost'      => $f->totalAccruedCostPaisa(),
                'projected' => $f->projectedProfitPaisa(),
                'isLoss'    => $f->isLoss(),
            ];
        });

        return view('projects.index', ['rows' => $rows, 'filters' => $request->only('status', 'q')]);
    }

    public function create()
    {
        return view('projects.create', [
            'project'          => new Project(['retention_percent' => Setting::get('default_retention'), 'status' => 'quoted', 'contract_type' => 'full_finished', 'pricing_mode' => 'per_sqft']),
            'defaultRetention' => Setting::get('default_retention'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $project = Project::create($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project ban gaya.');
    }

    public function show(Project $project)
    {
        $project->load(['estimates', 'clientPayments' => fn ($q) => $q->orderBy('date'),
            'retentionReleases' => fn ($q) => $q->orderBy('date'),
            'materialPurchases.vendor', 'otherExpenses']);

        $f = ProjectFinance::for($project);

        // Estimate vs Actual rows (8 categories).
        $est = $f->estimateByCategory();
        $act = $f->actualByCategory();
        $variance = [];
        foreach ($est as $cat => $estAmt) {
            $actAmt = $act[$cat] ?? 0;
            $variance[] = [
                'category' => $cat,
                'estimate' => $estAmt,
                'actual'   => $actAmt,
                'variance' => $actAmt - $estAmt,
                'pct'      => $estAmt > 0 ? round(($actAmt - $estAmt) / $estAmt * 100, 1) : null,
                'over'     => $actAmt > $estAmt && $estAmt > 0,
            ];
        }

        // Per-project labour summary (which workers worked here + earned/paid on THIS project).
        $earned = \App\Models\WorkEntry::where('project_id', $project->id)
            ->selectRaw('worker_id, SUM(computed_wage_paisa) as earned, SUM(COALESCE(days_present,0)) as days')
            ->groupBy('worker_id')->get()->keyBy('worker_id');
        $paid = \App\Models\WagePayment::where('project_id', $project->id)
            ->selectRaw('worker_id, SUM(amount_paisa) as paid')
            ->groupBy('worker_id')->pluck('paid', 'worker_id');
        $workerIds = $earned->keys()->merge($paid->keys())->unique();
        $projectWorkers = \App\Models\Worker::whereIn('id', $workerIds)->orderBy('name')->get()->map(fn ($w) => [
            'worker' => $w,
            'days'   => (float) ($earned[$w->id]->days ?? 0),
            'earned' => (int) ($earned[$w->id]->earned ?? 0),
            'paid'   => (int) ($paid[$w->id] ?? 0),
        ]);
        $allWorkers = \App\Models\Worker::orderBy('name')->get(['id', 'name', 'wage_type']);

        // ---- Attendance day-grid: ONE month (dropdown), default current month ----
        $attWorkers = \App\Models\Worker::where('wage_type', '!=', 'contract_piece')->orderBy('name')->get();
        $attMonth = request('att_month', now()->format('Y-m'));
        try {
            $gridStart = \Carbon\Carbon::createFromFormat('Y-m-d', $attMonth . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $gridStart = now()->startOfMonth();
            $attMonth = $gridStart->format('Y-m');
        }
        $gridEnd = $gridStart->copy()->endOfMonth();
        $attDays = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $attDays[] = ['date' => $d->format('Y-m-d'), 'd' => $d->day, 'mon' => $d->format('M'), 'wd' => $d->format('D')[0], 'fri' => $d->isFriday()];
        }
        // Month options: current month back to (project start OR 18 months, whichever is earlier).
        $attMonthOptions = [];
        $omEnd = now()->startOfMonth();
        $earliest = $omEnd->copy()->subMonths(18);
        if ($project->start_date && $project->start_date->copy()->startOfMonth()->lt($earliest)) {
            $earliest = $project->start_date->copy()->startOfMonth();
        }
        for ($m = $omEnd->copy(); $m->gte($earliest); $m->subMonth()) {
            $attMonthOptions[] = ['value' => $m->format('Y-m'), 'label' => $m->format('F Y')];
        }
        // Which (worker, date) already have attendance (any project) -> locked.
        $attMarked = [];
        \App\Models\WorkEntry::whereBetween('date', [$gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d')])
            ->get(['worker_id', 'date'])->each(function ($e) use (&$attMarked) {
                $attMarked[$e->worker_id][$e->date->format('Y-m-d')] = true;
            });

        // Data for the in-project tabs.
        $estimatesGrouped = $project->estimates;
        $vendors = \App\Models\Vendor::orderBy('name')->get(['id', 'name', 'type']);
        $defaultRetention = Setting::get('default_retention');
        $defaultWastage = Setting::get('default_wastage');
        $estimateCategories = \App\Http\Controllers\EstimateController::CATEGORIES;
        $expenseCategories = \App\Http\Controllers\MoneyOutController::EXPENSE_CATEGORIES;

        return view('projects.show', compact(
            'project', 'f', 'variance', 'projectWorkers', 'allWorkers',
            'vendors', 'defaultRetention', 'defaultWastage', 'estimateCategories', 'expenseCategories',
            'attWorkers', 'attDays', 'attMarked', 'attMonth', 'attMonthOptions'
        ));
    }

    /** Bulk mark attendance from the project day-grid (selected cells = present). */
    public function storeBulkAttendance(Request $request, Project $project)
    {
        $cells = json_decode($request->input('cells', '[]'), true) ?: [];
        $created = 0; $skipped = 0;
        $workers = \App\Models\Worker::whereIn('id', collect($cells)->map(fn ($c) => explode('|', $c)[0])->unique())->get()->keyBy('id');

        foreach ($cells as $cell) {
            [$wid, $date] = array_pad(explode('|', $cell), 2, null);
            $worker = $workers[$wid] ?? null;
            if (! $worker || ! $date) { continue; }
            if (\App\Models\WorkEntry::where('worker_id', $wid)->whereDate('date', $date)->exists()) {
                $skipped++; continue;
            }
            \App\Models\WorkEntry::create([
                'worker_id' => $wid, 'project_id' => $project->id, 'date' => $date,
                'days_present' => 1, 'computed_wage_paisa' => (int) $worker->default_wage_paisa,
            ]);
            $created++;
        }

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'attendance', 'att_month' => $request->input('att_month')])
            ->with('status', "Haazri lag gayi — {$created} din" . ($skipped ? ", {$skipped} pehle se thi" : '') . '.');
    }

    // ---------- In-project quick-add actions (project hub) ----------

    public function storePayment(Request $request, Project $project)
    {
        $v = $request->validate([
            'date'           => ['required', 'date'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,bank,cheque,online'],
            'is_advance'     => ['nullable', 'boolean'],
            'notes'          => ['nullable', 'string'],
        ]);

        // Simple model: amount given = received. No retention/net split.
        $amount = \App\Support\Money::toPaisa($v['amount']);
        $project->clientPayments()->create([
            'date' => $v['date'], 'gross_amount_paisa' => $amount,
            'retention_held_paisa' => 0, 'net_received_paisa' => $amount,
            'payment_method' => $v['payment_method'],
            'is_mobilization' => $request->boolean('is_advance'),
            'notes' => $v['notes'] ?? null,
        ]);

        return $this->backToTab($project, 'money-in', 'Payment record ho gayi.');
    }

    public function storeRelease(Request $request, Project $project)
    {
        $v = $request->validate(['date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string']]);
        $project->retentionReleases()->create([
            'date' => $v['date'], 'amount_paisa' => \App\Support\Money::toPaisa($v['amount']), 'notes' => $v['notes'] ?? null,
        ]);

        return $this->backToTab($project, 'money-in', 'Retention release record ho gayi.');
    }

    public function storeMaterial(Request $request, Project $project)
    {
        $v = $request->validate([
            'vendor_id'     => ['nullable', 'exists:vendors,id'],
            'date'          => ['required', 'date'],
            'item_name'     => ['required', 'string', 'max:255'],
            'qty'           => ['required', 'numeric', 'min:0'],
            'unit'          => ['nullable', 'string', 'max:50'],
            'rate_per_unit' => ['required', 'numeric', 'min:0'],
            'amount_paid'   => ['nullable', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string'],
        ]);
        $amount = (int) round((float) $v['qty'] * \App\Support\Money::toPaisa($v['rate_per_unit']));
        $paid = min(\App\Support\Money::toPaisa($v['amount_paid'] ?? 0), $amount);

        $project->materialPurchases()->create([
            'vendor_id' => $v['vendor_id'] ?? null, 'date' => $v['date'], 'item_name' => $v['item_name'],
            'qty' => $v['qty'], 'unit' => $v['unit'] ?? null,
            'rate_per_unit_paisa' => \App\Support\Money::toPaisa($v['rate_per_unit']),
            'amount_paisa' => $amount, 'amount_paid_paisa' => $paid, 'balance_due_paisa' => $amount - $paid,
            'notes' => $v['notes'] ?? null,
        ]);

        return $this->backToTab($project, 'materials', 'Purchase record ho gayi.');
    }

    public function storeExpense(Request $request, Project $project)
    {
        $v = $request->validate([
            'date'        => ['required', 'date'],
            'category'    => ['required', 'in:' . implode(',', MoneyOutController::EXPENSE_CATEGORIES)],
            'description' => ['nullable', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'paid_to'     => ['nullable', 'string', 'max:255'],
        ]);
        $project->otherExpenses()->create([
            'date' => $v['date'], 'category' => $v['category'], 'description' => $v['description'] ?? null,
            'amount_paisa' => \App\Support\Money::toPaisa($v['amount']), 'paid_to' => $v['paid_to'] ?? null,
        ]);

        return $this->backToTab($project, 'expenses', 'Expense record ho gaya.');
    }

    private function backToTab(Project $project, string $tab, string $msg)
    {
        return redirect()->route('projects.show', ['project' => $project, 'tab' => $tab])->with('status', $msg);
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $project->update($this->validated($request));

        return redirect()->route('projects.show', $project)->with('status', 'Project update ho gaya.');
    }

    public function destroy(Project $project)
    {
        $project->delete(); // soft delete

        return redirect()->route('projects.index')->with('status', 'Project hata diya (soft delete).');
    }

    /** Validate + normalise (rupees -> paisa, compute contract value). */
    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'client_name'       => ['nullable', 'string', 'max:255'],
            'client_phone'      => ['nullable', 'string', 'max:50'],
            'client_address'    => ['nullable', 'string', 'max:500'],
            'contract_type'     => ['required', 'in:grey_structure,full_finished'],
            'pricing_mode'      => ['required', 'in:per_sqft,lump_sum'],
            'covered_area_sqft' => ['nullable', 'numeric', 'min:0', 'required_if:pricing_mode,per_sqft'],
            'rate_per_sqft'     => ['nullable', 'numeric', 'min:0', 'required_if:pricing_mode,per_sqft'],
            'contract_value'    => ['nullable', 'numeric', 'min:0', 'required_if:pricing_mode,lump_sum'],
            'retention_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'completion_percent'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'start_date'        => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date'],
            'actual_end_date'   => ['nullable', 'date'],
            'status'            => ['required', 'in:quoted,active,on_hold,completed,closed'],
            'notes'             => ['nullable', 'string'],
        ]);

        $perSqft = $v['pricing_mode'] === 'per_sqft';
        $ratePaisa = $perSqft ? \App\Support\Money::toPaisa($v['rate_per_sqft']) : null;
        $area = $perSqft ? (float) $v['covered_area_sqft'] : null;

        $contractValue = $perSqft
            ? (int) round($area * $ratePaisa)
            : \App\Support\Money::toPaisa($v['contract_value']);

        return [
            'name'                 => $v['name'],
            'client_name'          => $v['client_name'] ?? null,
            'client_phone'         => $v['client_phone'] ?? null,
            'client_address'       => $v['client_address'] ?? null,
            'contract_type'        => $v['contract_type'],
            'pricing_mode'         => $v['pricing_mode'],
            'covered_area_sqft'    => $area,
            'rate_per_sqft_paisa'  => $ratePaisa,
            'contract_value_paisa' => $contractValue,
            'retention_percent'    => $v['retention_percent'] ?? 0,
            'completion_percent'   => $v['completion_percent'] ?? 0,
            'start_date'           => $v['start_date'] ?? null,
            'expected_end_date'    => $v['expected_end_date'] ?? null,
            'actual_end_date'      => $v['actual_end_date'] ?? null,
            'status'               => $v['status'],
            'notes'                => $v['notes'] ?? null,
        ];
    }
}
