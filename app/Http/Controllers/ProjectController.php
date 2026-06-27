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
        // Project-scoped adjustments: advance_given (deduction/peshgi) − recovery (bonus).
        $advGiven = \App\Models\WorkerAdvance::where('project_id', $project->id)->where('type', 'advance_given')
            ->selectRaw('worker_id, SUM(amount_paisa) as t')->groupBy('worker_id')->pluck('t', 'worker_id');
        $advRecov = \App\Models\WorkerAdvance::where('project_id', $project->id)->where('type', 'recovery')
            ->selectRaw('worker_id, SUM(amount_paisa) as t')->groupBy('worker_id')->pluck('t', 'worker_id');
        $workerIds = $earned->keys()->merge($paid->keys())->merge($advGiven->keys())->merge($advRecov->keys())->unique();
        $projectWorkers = \App\Models\Worker::whereIn('id', $workerIds)->orderBy('name')->get()->map(fn ($w) => [
            'worker' => $w,
            'days'   => (float) ($earned[$w->id]->days ?? 0),
            'earned' => (int) ($earned[$w->id]->earned ?? 0),
            'paid'   => (int) ($paid[$w->id] ?? 0),
            'advnet' => (int) ($advGiven[$w->id] ?? 0) - (int) ($advRecov[$w->id] ?? 0), // deduction − bonus
        ]);
        $allWorkers = \App\Models\Worker::orderBy('name')->get(['id', 'name', 'role', 'wage_type']);

        // ---- Attendance: per-worker calendar modal. Load ALL marked days (value = days_present) ----
        $attWorkers = \App\Models\Worker::where('wage_type', '!=', 'contract_piece')->orderBy('name')->get();
        $attMarked = [];
        \App\Models\WorkEntry::where('project_id', $project->id)
            ->get(['worker_id', 'date', 'days_present'])->each(function ($e) use (&$attMarked) {
                $attMarked[$e->worker_id][$e->date->format('Y-m-d')] = (float) $e->days_present;
            });
        $startYear = $project->start_date ? (int) $project->start_date->format('Y') : (int) now()->format('Y');
        $startYear = min($startYear, (int) now()->format('Y'));
        $attYears = range((int) now()->format('Y'), $startYear); // current .. earliest
        $attMonthNames = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec'];
        $attToday   = now()->format('Y-m-d');
        $attCurYear = (int) now()->format('Y');
        $attCurMon  = now()->format('m');

        // Data for the in-project tabs.
        $estimatesGrouped = $project->estimates;
        $vendors = \App\Models\Vendor::orderBy('name')->get(['id', 'name', 'type']);
        $defaultRetention = Setting::get('default_retention');
        $defaultWastage = Setting::get('default_wastage');
        $estimateCategories = \App\Http\Controllers\EstimateController::CATEGORIES;
        $expenseCategories = \App\Http\Controllers\MoneyOutController::EXPENSE_CATEGORIES;
        $adjustments = \App\Models\WorkerAdvance::with('worker')->where('project_id', $project->id)
            ->orderByDesc('date')->orderByDesc('id')->get();
        $workerRoles = Setting::workerRoles();

        return view('projects.show', compact(
            'project', 'f', 'variance', 'projectWorkers', 'allWorkers',
            'vendors', 'defaultRetention', 'defaultWastage', 'estimateCategories', 'expenseCategories',
            'attWorkers', 'attMarked', 'attYears', 'attMonthNames', 'attToday', 'attCurYear', 'attCurMon',
            'adjustments', 'workerRoles'
        ));
    }

    /** Mark attendance for ONE worker from the calendar modal. Status -> days_present (1 / 0.5 / 0). */
    public function storeBulkAttendance(Request $request, Project $project)
    {
        $v = $request->validate([
            'worker_id' => ['required', 'exists:workers,id'],
            'status'    => ['required', 'in:present,half,absent'],
            'dates'     => ['required', 'string'],
        ]);
        $worker = \App\Models\Worker::findOrFail($v['worker_id']);
        $dates  = json_decode($v['dates'], true) ?: [];
        $dayVal = ['present' => 1.0, 'half' => 0.5, 'absent' => 0.0][$v['status']];
        $today  = now()->format('Y-m-d');

        $created = 0; $skipped = 0;
        foreach ($dates as $date) {
            if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { continue; }
            if ($date > $today) { continue; } // never mark future
            if (\App\Models\WorkEntry::where('worker_id', $worker->id)->where('project_id', $project->id)->whereDate('date', $date)->exists()) {
                $skipped++; continue;
            }
            \App\Models\WorkEntry::create([
                'worker_id' => $worker->id, 'project_id' => $project->id, 'date' => $date,
                'days_present' => $dayVal,
                'computed_wage_paisa' => (int) round((int) $worker->default_wage_paisa * $dayVal),
            ]);
            $created++;
        }

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'attendance'])
            ->with('status', "Haazri lagi — {$created} din" . ($skipped ? ", {$skipped} pehle se thi" : '') . '.');
    }

    /** Sub-contractor (theka) deal — reuses vendor(type=subcontractor) + material_purchase row. */
    public function storeSubcontractor(Request $request, Project $project)
    {
        $v = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'new_name'  => ['nullable', 'string', 'max:255'],
            'date'      => ['required', 'date'],
            'work'      => ['required', 'string', 'max:255'],
            'agreed'    => ['required', 'numeric', 'min:0'],
            'paid'      => ['nullable', 'numeric', 'min:0'],
        ]);

        $vendorId = $v['vendor_id'] ?? null;
        if (! $vendorId) {
            if (empty($v['new_name'])) {
                return back()->with('error', 'Sub-contractor chuno ya naya naam likho.');
            }
            $vendorId = \App\Models\Vendor::create(['name' => $v['new_name'], 'type' => 'subcontractor'])->id;
        }

        $agreed = \App\Support\Money::toPaisa($v['agreed']);
        $paid   = min(\App\Support\Money::toPaisa($v['paid'] ?? 0), $agreed);
        $project->materialPurchases()->create([
            'vendor_id' => $vendorId, 'date' => $v['date'], 'item_name' => $v['work'],
            'qty' => 1, 'unit' => 'theka', 'rate_per_unit_paisa' => $agreed,
            'amount_paisa' => $agreed, 'amount_paid_paisa' => $paid, 'balance_due_paisa' => $agreed - $paid,
        ]);

        return $this->backToTab($project, 'subcontractor', 'Sub-contractor theka record ho gaya.');
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

    /** Worker adjustment: deduction (katauti) or bonus, project-scoped. */
    public function storeAdjustment(Request $request, Project $project)
    {
        $v = $request->validate([
            'worker_id' => ['required', 'exists:workers,id'],
            'date'      => ['required', 'date'],
            'type'      => ['required', 'in:deduction,bonus'],
            'amount'    => ['required', 'numeric', 'min:0'],
            'notes'     => ['nullable', 'string'],
        ]);

        // deduction -> advance_given (reduces what we owe); bonus -> recovery (increases).
        \App\Models\WorkerAdvance::create([
            'worker_id'    => $v['worker_id'],
            'project_id'   => $project->id,
            'date'         => $v['date'],
            'type'         => $v['type'] === 'deduction' ? 'advance_given' : 'recovery',
            'amount_paisa' => \App\Support\Money::toPaisa($v['amount']),
            'notes'        => ($v['type'] === 'deduction' ? 'Katauti: ' : 'Bonus: ') . ($v['notes'] ?? ''),
        ]);

        return $this->backToTab($project, 'adjustment', 'Adjustment record ho gaya.');
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
            'contract_type'     => ['required', 'in:structure,grey_structure,full_finished'],
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
