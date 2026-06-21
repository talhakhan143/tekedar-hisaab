<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WorkEntry;
use App\Models\Worker;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Fast daily attendance: mark ALL workers in one screen, for a single day
 * OR a date range / whole month. Already-marked days are skipped (no double wage).
 */
class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Workers paid daily/monthly fit the grid; piece workers are handled on their own page.
        $workers = Worker::where('wage_type', '!=', 'contract_piece')->orderBy('name')->get();
        $pieceWorkers = Worker::where('wage_type', 'contract_piece')->orderBy('name')->get();

        return view('attendance.index', [
            'workers'      => $workers,
            'pieceWorkers' => $pieceWorkers,
            'projects'     => Project::orderByRaw("FIELD(status,'active') DESC")->orderBy('name')->get(['id', 'name', 'status']),
            'today'        => now()->format('Y-m-d'),
            'monthStart'   => now()->startOfMonth()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'mode'         => ['required', 'in:single,range'],
            'project_id'   => ['nullable', 'exists:projects,id'],
            'date'         => ['required_if:mode,single', 'nullable', 'date'],
            'from'         => ['required_if:mode,range', 'nullable', 'date'],
            'to'           => ['required_if:mode,range', 'nullable', 'date', 'after_or_equal:from'],
            'skip_fridays' => ['nullable', 'boolean'],
            'present'      => ['array'], // worker_id => '1' | '0.5' | '0'
        ]);

        // Build the list of dates.
        if ($v['mode'] === 'single') {
            $dates = [Carbon::parse($v['date'])];
        } else {
            $from = Carbon::parse($v['from']);
            $to = Carbon::parse($v['to']);
            if ($from->diffInDays($to) > 92) {
                return back()->with('error', 'Range bohot bada hai (max ~3 mahine). Chhota range chuno.');
            }
            $dates = [];
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                if ($request->boolean('skip_fridays') && $d->isFriday()) {
                    continue;
                }
                $dates[] = $d->copy();
            }
        }

        $created = 0;
        $skipped = 0;
        $workers = Worker::whereIn('id', array_keys($v['present'] ?? []))->get()->keyBy('id');

        foreach (($v['present'] ?? []) as $workerId => $val) {
            $days = (float) $val;
            if ($days <= 0) {
                continue; // absent
            }
            $worker = $workers[$workerId] ?? null;
            if (! $worker) {
                continue;
            }
            foreach ($dates as $d) {
                // Skip if already marked that day (prevents double wage).
                $exists = WorkEntry::where('worker_id', $worker->id)->whereDate('date', $d)->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }
                WorkEntry::create([
                    'worker_id'           => $worker->id,
                    'project_id'          => $v['project_id'] ?? null,
                    'date'                => $d->format('Y-m-d'),
                    'days_present'        => $days,
                    'computed_wage_paisa' => (int) round($days * $worker->default_wage_paisa),
                ]);
                $created++;
            }
        }

        return back()->with('status', "Attendance lag gayi — {$created} entries banayi" . ($skipped ? ", {$skipped} pehle se lagi thi (skip)" : '') . '.');
    }

    /** Monthly register: workers × days grid, shows which days are marked. */
    public function register(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $daysInMonth = $start->daysInMonth;

        $workers = Worker::where('wage_type', '!=', 'contract_piece')->orderBy('name')->get();
        $projectId = $request->integer('project_id') ?: null;

        // Existing entries for the month: [worker_id][day] => ['days'=>, 'project_id'=>, 'project'=>]
        $entries = WorkEntry::with('project')
            ->whereYear('date', $start->year)->whereMonth('date', $start->month)
            ->get();
        $existing = [];
        foreach ($entries as $e) {
            $existing[$e->worker_id][(int) $e->date->day] = [
                'days'       => (float) $e->days_present,
                'project_id' => $e->project_id,
                'project'    => $e->project?->name,
            ];
        }

        // Build day headers with weekday letters.
        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $c = $start->copy()->day($d);
            $days[] = ['n' => $d, 'wd' => $c->format('D')[0], 'fri' => $c->isFriday()];
        }

        return view('attendance.register', [
            'workers'    => $workers,
            'projects'   => Project::orderByRaw("FIELD(status,'active') DESC")->orderBy('name')->get(['id', 'name', 'status']),
            'days'       => $days,
            'existing'   => $existing,
            'month'      => $month,
            'monthLabel' => $start->format('F Y'),
            'prevMonth'  => $start->copy()->subMonth()->format('Y-m'),
            'nextMonth'  => $start->copy()->addMonth()->format('Y-m'),
            'projectId'  => $projectId,
        ]);
    }

    public function saveRegister(Request $request)
    {
        $v = $request->validate([
            'month'      => ['required', 'string'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'cells'      => ['required', 'string'], // JSON: {workerId:{day:'1'|'0.5'|''}}
        ]);

        $start = Carbon::createFromFormat('Y-m-d', $v['month'] . '-01')->startOfMonth();
        $grid = json_decode($v['cells'], true) ?: [];
        $projectId = $v['project_id'] ?? null;

        $created = 0; $updated = 0; $removed = 0; $locked = 0;

        foreach ($grid as $workerId => $cells) {
            foreach ($cells as $day => $val) {
                $date = $start->copy()->day((int) $day);
                $existing = WorkEntry::where('worker_id', $workerId)->whereDate('date', $date)->first();

                // Don't touch entries that belong to a different project.
                if ($existing && (int) $existing->project_id !== (int) $projectId && $existing->project_id !== null) {
                    $locked++;
                    continue;
                }

                $days = (float) $val;
                if ($days > 0) {
                    $worker = Worker::find($workerId);
                    if (! $worker) { continue; }
                    $wage = (int) round($days * $worker->default_wage_paisa);
                    if ($existing) {
                        $existing->update(['days_present' => $days, 'computed_wage_paisa' => $wage, 'project_id' => $projectId]);
                        $updated++;
                    } else {
                        WorkEntry::create([
                            'worker_id' => $workerId, 'project_id' => $projectId, 'date' => $date->format('Y-m-d'),
                            'days_present' => $days, 'computed_wage_paisa' => $wage,
                        ]);
                        $created++;
                    }
                } elseif ($existing) {
                    $existing->delete();
                    $removed++;
                }
            }
        }

        $msg = "Register save: {$created} nayi, {$updated} update, {$removed} hatai" . ($locked ? ", {$locked} doosre project ki (chhoda)" : '') . '.';

        return redirect()->route('attendance.register', ['month' => $v['month'], 'project_id' => $projectId])->with('status', $msg);
    }

    /** Inline create a worker without leaving the attendance screen. */
    public function quickWorker(Request $request)
    {
        $v = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'role'         => ['required', 'in:mistri,mazdoor,electrician,plumber,painter,foreman,other'],
            'default_wage' => ['required', 'numeric', 'min:0'],
        ]);

        Worker::create([
            'name'               => $v['name'],
            'role'               => $v['role'],
            'wage_type'          => 'daily',
            'default_wage_paisa' => Money::toPaisa($v['default_wage']),
        ]);

        return back()->with('status', $v['name'] . ' add ho gaya. Ab haazri lag sakti hai.');
    }
}
