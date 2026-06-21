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
