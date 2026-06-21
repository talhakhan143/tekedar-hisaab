<?php

namespace App\Http\Controllers;

use App\Models\RetentionRelease;
use App\Support\Money;
use Illuminate\Http\Request;

class RetentionReleaseController extends Controller
{
    public function store(Request $request)
    {
        $v = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'date'       => ['required', 'date'],
            'amount'     => ['required', 'numeric', 'min:0'],
            'notes'      => ['nullable', 'string'],
        ]);

        RetentionRelease::create([
            'project_id'   => $v['project_id'],
            'date'         => $v['date'],
            'amount_paisa' => Money::toPaisa($v['amount']),
            'notes'        => $v['notes'] ?? null,
        ]);

        return back()->with('status', 'Retention release record ho gayi.');
    }

    public function destroy(RetentionRelease $retentionRelease)
    {
        $retentionRelease->delete();

        return back()->with('status', 'Retention release hata di.');
    }
}
