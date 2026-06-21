<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\Project;
use App\Models\Setting;
use App\Services\ProjectFinance;
use App\Support\Money;
use Illuminate\Http\Request;

class EstimateController extends Controller
{
    public const CATEGORIES = ['material', 'labour', 'subcontractor', 'transport', 'equipment', 'utility', 'overhead', 'misc'];

    public function index(Project $project)
    {
        $project->load('estimates');
        $f = ProjectFinance::for($project);

        return view('estimates.index', [
            'project'         => $project,
            'estimates'       => $project->estimates->groupBy('category'),
            'estimateTotal'   => (int) $project->estimates->sum('amount_paisa'),
            'actualByCat'     => $f->actualByCategory(),
            'estimateByCat'   => $f->estimateByCategory(),
            'categories'      => self::CATEGORIES,
            'defaultWastage'  => Setting::get('default_wastage'),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $data = $this->validateInput($request);
        $data['project_id'] = $project->id;
        $data['amount_paisa'] = $this->computeAmount($data);
        Estimate::create($data);

        return back()->with('status', 'Estimate line add ho gayi.');
    }

    public function update(Request $request, Estimate $estimate)
    {
        $data = $this->validateInput($request);
        $data['amount_paisa'] = $this->computeAmount($data);
        $estimate->update($data);

        return back()->with('status', 'Estimate update ho gayi.');
    }

    public function destroy(Estimate $estimate)
    {
        $estimate->delete();

        return back()->with('status', 'Estimate line hata di.');
    }

    private function validateInput(Request $request): array
    {
        $v = $request->validate([
            'category'        => ['required', 'in:' . implode(',', self::CATEGORIES)],
            'item_name'       => ['required', 'string', 'max:255'],
            'unit'            => ['nullable', 'string', 'max:50'],
            'qty_estimated'   => ['required', 'numeric', 'min:0'],
            'rate_per_unit'   => ['required', 'numeric', 'min:0'],
            'wastage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'           => ['nullable', 'string'],
        ]);

        return [
            'category'            => $v['category'],
            'item_name'           => $v['item_name'],
            'unit'                => $v['unit'] ?? null,
            'qty_estimated'       => $v['qty_estimated'],
            'rate_per_unit_paisa' => Money::toPaisa($v['rate_per_unit']),
            'wastage_percent'     => $v['wastage_percent'] ?? 0,
            'notes'               => $v['notes'] ?? null,
        ];
    }

    /** Material amount includes wastage inflation; others = qty × rate. */
    private function computeAmount(array $data): int
    {
        $qty = (float) $data['qty_estimated'];
        if ($data['category'] === 'material') {
            $qty *= (1 + (float) $data['wastage_percent'] / 100);
        }
        return (int) round($qty * $data['rate_per_unit_paisa']);
    }
}
