<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WagePayment;
use App\Models\Worker;
use App\Models\WorkEntry;
use App\Models\WorkerAdvance;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Handles the three sub-ledgers on a worker: attendance/work, advances (peshgi),
 * and wage payments. Kept in one controller since they all hang off a worker.
 */
class WorkLedgerController extends Controller
{
    /** One attendance per worker per day (prevents double-counting wages). */
    private function attendanceExists(int $workerId, string $date, ?int $ignoreId = null): bool
    {
        return WorkEntry::where('worker_id', $workerId)
            ->whereDate('date', $date)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /** Compute a worker's wage for a work entry (daily/monthly vs piece). */
    private function wageFor(Worker $worker, ?float $days, ?float $units): int
    {
        if ($worker->wage_type === 'contract_piece') {
            return (int) round((float) ($units ?? 0) * $worker->default_wage_paisa);
        }
        return (int) round((float) ($days ?? 0) * $worker->default_wage_paisa);
    }

    // ---------- Project-scoped quick entry (from the project page) ----------
    public function storeProjectWork(Request $request, Project $project)
    {
        $v = $request->validate([
            'worker_id'    => ['required', 'exists:workers,id'],
            'date'         => ['required', 'date'],
            'days_present' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'units_done'   => ['nullable', 'numeric', 'min:0'],
        ]);

        $worker = Worker::findOrFail($v['worker_id']);

        $tabUrl = route('projects.show', ['project' => $project, 'tab' => 'attendance']);

        if ($this->attendanceExists($worker->id, $v['date'])) {
            return redirect($tabUrl)->with('error', $worker->name . ' ki ' . \Carbon\Carbon::parse($v['date'])->format('d-m-Y') . ' ki haazri pehle lag chuki hai. Dobara nahi lag sakti.');
        }

        WorkEntry::create([
            'worker_id'           => $worker->id,
            'project_id'          => $project->id,
            'date'                => $v['date'],
            'days_present'        => $v['days_present'] ?? null,
            'units_done'          => $v['units_done'] ?? null,
            'computed_wage_paisa' => $this->wageFor($worker, $v['days_present'] ?? null, $v['units_done'] ?? null),
        ]);

        return redirect($tabUrl)->with('status', $worker->name . ' ki haazri lag gayi.');
    }

    public function storeProjectWage(Request $request, Project $project)
    {
        $v = $request->validate([
            'worker_id'    => ['required', 'exists:workers,id'],
            'date'         => ['required', 'date'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'period_label' => ['nullable', 'string', 'max:100'],
            'override'     => ['nullable', 'boolean'],
            'all_projects' => ['nullable', 'boolean'],
        ]);

        $worker      = Worker::findOrFail($v['worker_id']);
        $amount      = Money::toPaisa($v['amount']);
        $allProjects = $request->boolean('all_projects');

        $dues    = Worker::duesByProjectFor([$worker->id])[$worker->id] ?? [];
        $thisDue = max(0, $dues[$project->id] ?? 0);

        // Checkbox off = sirf is project ka khaata, is liye cap bhi isi project ka.
        // Pehle cap global payable tha, jis se doosre project ka paisa bhi is
        // project ke sar par chadh jata tha aur us ka P&L jhoot bolta.
        $cap = $allProjects ? $worker->payablePaisa() : $thisDue;

        if ($amount > $cap && ! $request->boolean('override')) {
            throw ValidationException::withMessages([
                'amount' => $allProjects
                    ? $worker->name . ' ka sab projects mila kar baqi sirf ' . Money::format($cap) . ' hai. Zyada dena hai to override check karo.'
                    : $worker->name . ' ka is project me baqi sirf ' . Money::format($cap) . ' hai. Doosre projects ke dues bhi dene hain to "sab projects" wala checkbox lagao, warna override check karo.',
            ]);
        }

        $allocation = $this->allocateWage($amount, $dues, $project->id, $allProjects);

        $payments = [];
        foreach ($allocation as $projectId => $slice) {
            $payments[] = WagePayment::create([
                'worker_id'    => $worker->id,
                'project_id'   => $projectId ?: null,
                'date'         => $v['date'],
                'amount_paisa' => $slice,
                'period_label' => $v['period_label'] ?? null,
                'notes'        => count($allocation) > 1 ? 'Sab projects ke dues ek sath diye gaye.' : null,
            ]);
        }

        $redirect = redirect(route('projects.show', ['project' => $project, 'tab' => 'attendance']));

        if (count($payments) > 1) {
            return $redirect
                ->with('status', $worker->name . ' ko ' . Money::format($amount) . ' pay ho gayi, ' . count($payments) . ' projects me baant kar.')
                ->with('voucher_url', route('vouchers.worker-statement', $worker))
                ->with('voucher_label', '🖨 Worker Statement');
        }

        return $redirect
            ->with('status', $worker->name . ' ko wage pay ho gayi.')
            ->with('voucher_url', route('vouchers.wage', $payments[0]))
            ->with('voucher_label', '🖨 Wage Voucher');
    }

    /**
     * Di gayi rakam ko project ke khaaton me baanto.
     *
     * Checkbox off ho to sab kuch isi project par. On ho to pehle isi project
     * ka baqi pura karo, phir doosre projects purane se naye, aur "bina
     * project" wala khaata sab se aakhir me. Override ki wajah se agar rakam
     * kul baqi se zyada ho to bacha hua hissa isi project par daal dete hain.
     *
     * @param  array<int, int>  $dues  [project_id => baqi_paisa], 0 = bina project
     * @return array<int, int>  [project_id => kitna is khaate me jayega]
     */
    private function allocateWage(int $amount, array $dues, int $currentProjectId, bool $allProjects): array
    {
        if ($amount <= 0) {
            return [];
        }

        if (! $allProjects) {
            return [$currentProjectId => $amount];
        }

        $owed = array_filter($dues, fn ($d) => $d > 0);

        $order = [];
        if (($owed[$currentProjectId] ?? 0) > 0) {
            $order[] = $currentProjectId;
        }

        $others = array_keys(array_diff_key($owed, [$currentProjectId => true, 0 => true]));
        if ($others !== []) {
            $order = array_merge($order, Project::whereIn('id', $others)
                ->orderByRaw('start_date IS NULL, start_date')
                ->orderBy('id')
                ->pluck('id')->all());
        }

        if (($owed[0] ?? 0) > 0) {
            $order[] = 0; // bina project wala khaata
        }

        $allocation = [];
        $left = $amount;

        foreach ($order as $projectId) {
            if ($left <= 0) {
                break;
            }
            $take = min($left, $owed[$projectId]);
            if ($take > 0) {
                $allocation[$projectId] = $take;
                $left -= $take;
            }
        }

        if ($left > 0) {
            $allocation[$currentProjectId] = ($allocation[$currentProjectId] ?? 0) + $left;
        }

        return $allocation;
    }

    public function storeWork(Request $request, Worker $worker)
    {
        $v = $request->validate([
            'project_id'   => ['nullable', 'exists:projects,id'],
            'date'         => ['required', 'date'],
            'days_present' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'units_done'   => ['nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string'],
        ]);

        if ($this->attendanceExists($worker->id, $v['date'])) {
            return back()->with('error', $worker->name . ' ki ' . \Carbon\Carbon::parse($v['date'])->format('d-m-Y') . ' ki haazri pehle lag chuki hai. Dobara nahi lag sakti.');
        }

        WorkEntry::create([
            'worker_id'           => $worker->id,
            'project_id'          => $v['project_id'] ?? null,
            'date'                => $v['date'],
            'days_present'        => $v['days_present'] ?? null,
            'units_done'          => $v['units_done'] ?? null,
            'computed_wage_paisa' => $this->wageFor($worker, $v['days_present'] ?? null, $v['units_done'] ?? null),
            'notes'               => $v['notes'] ?? null,
        ]);

        return back()->with('status', 'Attendance/work entry add ho gayi.');
    }

    public function destroyWork(WorkEntry $workEntry)
    {
        $workEntry->delete();

        return back()->with('status', 'Entry hata di.');
    }

    public function storeAdvance(Request $request, Worker $worker)
    {
        $v = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'date'       => ['required', 'date'],
            'type'       => ['required', 'in:advance_given,recovery'],
            'amount'     => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string'],
        ]);

        $advance = WorkerAdvance::create([
            'worker_id'   => $worker->id,
            'project_id'  => $v['project_id'] ?? null,
            'date'        => $v['date'],
            'type'        => $v['type'],
            'amount_paisa'=> Money::toPaisa($v['amount']),
            'notes'       => $v['notes'] ?? null,
        ]);

        return back()->with('status', 'Advance/recovery record ho gayi.')
            ->with('voucher_url', route('vouchers.advance', $advance))
            ->with('voucher_label', '🖨 Advance Voucher');
    }

    public function destroyAdvance(WorkerAdvance $workerAdvance)
    {
        $workerAdvance->delete();

        return back()->with('status', 'Advance hata di.');
    }

    public function storePayment(Request $request, Worker $worker)
    {
        $v = $request->validate([
            'project_id'   => ['nullable', 'exists:projects,id'],
            'date'         => ['required', 'date'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'period_label' => ['nullable', 'string', 'max:100'],
            'override'     => ['nullable', 'boolean'],
            'notes'        => ['nullable', 'string'],
        ]);

        $amount = Money::toPaisa($v['amount']);
        $payable = $worker->payablePaisa();

        // Block paying more than payable unless override flag is set.
        if ($amount > $payable && ! ($v['override'] ?? false)) {
            throw ValidationException::withMessages([
                'amount' => 'Payable sirf ' . Money::format($payable) . ' hai. Zyada dena hai to "override" check karo.',
            ]);
        }

        $wp = WagePayment::create([
            'worker_id'   => $worker->id,
            'project_id'  => $v['project_id'] ?? null,
            'date'        => $v['date'],
            'amount_paisa'=> $amount,
            'period_label'=> $v['period_label'] ?? null,
            'notes'       => $v['notes'] ?? null,
        ]);

        return back()->with('status', 'Wage payment record ho gayi.')
            ->with('voucher_url', route('vouchers.wage', $wp))
            ->with('voucher_label', '🖨 Wage Voucher');
    }

    public function destroyPayment(WagePayment $wagePayment)
    {
        $wagePayment->delete();

        return back()->with('status', 'Payment hata di.');
    }
}
