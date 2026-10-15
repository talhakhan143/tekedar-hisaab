<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Worker;
use App\Support\Money;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    /**
     * Role ki list ab sirf Settings me rehti hai. Ye hardcoded constant usi
     * se alag ho gayi thi, jis ki wajah se Settings me role badalte hi worker
     * add karna band ho jata tha.
     *
     * Update karte waqt bande ka mojooda role bhi allow karte hain, warna
     * Settings se koi role nikaalne par us role wale purane workers kabhi
     * save hi na ho paate.
     */
    private function allowedRoles(?Worker $worker = null): array
    {
        $roles = Setting::workerRoles();

        if ($worker && $worker->role && ! in_array($worker->role, $roles, true)) {
            $roles[] = $worker->role;
        }

        return $roles;
    }
    public const WAGE_TYPES = ['daily', 'monthly', 'contract_piece'];

    public function index()
    {
        $rows = Worker::orderBy('name')->paginate(20);
        $rows->through(fn (Worker $w) => [
            'worker'   => $w,
            'earned'   => $w->earnedPaisa(),
            'advances' => $w->advancesOutstandingPaisa(),
            'payable'  => $w->payablePaisa(),
        ]);

        // Company-wide advances outstanding (all workers, not just this page).
        $given = (int) \App\Models\WorkerAdvance::where('type', 'advance_given')->sum('amount_paisa');
        $recov = (int) \App\Models\WorkerAdvance::where('type', 'recovery')->sum('amount_paisa');
        $totalAdvances = max(0, $given - $recov);

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
        $worker->update($this->validated($request, $worker));

        return redirect()->route('workers.show', $worker)->with('status', 'Worker update ho gaya.');
    }

    /**
     * Sirf dihaadi badalne ke liye, taake project page chhorna na pare.
     *
     * Ye sirf aage ki haazri par lagti hai. Purani entries ka wage us waqt
     * hi work_entries.computed_wage_paisa me likh dia gaya tha, is liye
     * purana hisaab apni jagah rehta hai aur kisi ka kamaya hua nahi badalta.
     */
    public function updateWage(Request $request, Worker $worker)
    {
        $v = $request->validate([
            'default_wage' => ['required', 'numeric', 'min:0'],
        ]);

        $worker->update(['default_wage_paisa' => Money::toPaisa($v['default_wage'])]);

        return back()->with('status', $worker->name . ' ki dihaadi ab ' . Money::format($worker->default_wage_paisa) . ' hai. Purani haazri waise hi rahegi.');
    }

    public function destroy(Worker $worker)
    {
        $worker->delete();

        return redirect()->route('workers.index')->with('status', 'Worker hata diya.');
    }

    private function validated(Request $request, ?Worker $worker = null): array
    {
        $v = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'role'         => ['required', \Illuminate\Validation\Rule::in($this->allowedRoles($worker))],
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
