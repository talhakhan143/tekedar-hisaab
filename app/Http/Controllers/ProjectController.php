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
        $projects = Project::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$term}%")->orWhere('client_name', 'like', "%{$term}%")))
            ->orderByDesc('id')
            ->get();

        // Attach lightweight P&L for the list.
        $rows = $projects->map(function (Project $p) {
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

        return view('projects.show', compact('project', 'f', 'variance', 'projectWorkers', 'allWorkers'));
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
