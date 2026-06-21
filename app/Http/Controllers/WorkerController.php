<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Worker;
use App\Support\Money;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public const ROLES = ['mistri', 'mazdoor', 'electrician', 'plumber', 'painter', 'foreman', 'other'];
    public const WAGE_TYPES = ['daily', 'monthly', 'contract_piece'];

    public function index()
    {
        $workers = Worker::orderBy('name')->get();
        $rows = $workers->map(fn (Worker $w) => [
            'worker'   => $w,
            'earned'   => $w->earnedPaisa(),
            'advances' => $w->advancesOutstandingPaisa(),
            'payable'  => $w->payablePaisa(),
        ]);
        $totalAdvances = $workers->sum(fn ($w) => max(0, $w->advancesOutstandingPaisa()));

        return view('workers.index', compact('rows', 'totalAdvances'));
    }

    public function create()
    {
        return view('workers.create', ['worker' => new Worker(['role' => 'mazdoor', 'wage_type' => 'daily'])]);
    }

    public function store(Request $request)
    {
        Worker::create($this->validated($request));

        return redirect()->route('workers.index')->with('status', 'Worker add ho gaya.');
    }

    public function show(Worker $worker)
    {
        $worker->load([
            'workEntries' => fn ($q) => $q->with('project')->orderByDesc('date'),
            'advances'    => fn ($q) => $q->with('project')->orderByDesc('date'),
            'wagePayments'=> fn ($q) => $q->with('project')->orderByDesc('date'),
        ]);

        return view('workers.show', [
            'worker'   => $worker,
            'earned'   => $worker->earnedPaisa(),
            'paid'     => $worker->paidPaisa(),
            'advances' => $worker->advancesOutstandingPaisa(),
            'payable'  => $worker->payablePaisa(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Worker $worker)
    {
        return view('workers.edit', compact('worker'));
    }

    public function update(Request $request, Worker $worker)
    {
        $worker->update($this->validated($request));

        return redirect()->route('workers.show', $worker)->with('status', 'Worker update ho gaya.');
    }

    public function destroy(Worker $worker)
    {
        $worker->delete();

        return redirect()->route('workers.index')->with('status', 'Worker hata diya.');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'role'         => ['required', 'in:' . implode(',', self::ROLES)],
            'wage_type'    => ['required', 'in:' . implode(',', self::WAGE_TYPES)],
            'default_wage' => ['required', 'numeric', 'min:0'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'notes'        => ['nullable', 'string'],
        ]);

        return [
            'name'               => $v['name'],
            'role'               => $v['role'],
            'wage_type'          => $v['wage_type'],
            'default_wage_paisa' => Money::toPaisa($v['default_wage']),
            'phone'              => $v['phone'] ?? null,
            'notes'              => $v['notes'] ?? null,
        ];
    }
}
