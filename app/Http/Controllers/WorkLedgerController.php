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
            'amount'       => ['required', 'numeric', 'min:0'],
            'period_label' => ['nullable', 'string', 'max:100'],
            'override'     => ['nullable', 'boolean'],
        ]);

        $worker = Worker::findOrFail($v['worker_id']);
        $amount = Money::toPaisa($v['amount']);

        if ($amount > $worker->payablePaisa() && ! $request->boolean('override')) {
            throw ValidationException::withMessages([
                'amount' => $worker->name . ' ka payable sirf ' . Money::format($worker->payablePaisa()) . ' hai. Zyada dena hai to override check karo.',
            ]);
        }

        $wp = WagePayment::create([
            'worker_id'    => $worker->id,
            'project_id'   => $project->id,
            'date'         => $v['date'],
            'amount_paisa' => $amount,
            'period_label' => $v['period_label'] ?? null,
        ]);

        return redirect(route('projects.show', ['project' => $project, 'tab' => 'attendance']))
            ->with('status', $worker->name . ' ko wage pay ho gayi.')
            ->with('voucher_url', route('vouchers.wage', $wp))
            ->with('voucher_label', '🖨 Wage Voucher');
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
