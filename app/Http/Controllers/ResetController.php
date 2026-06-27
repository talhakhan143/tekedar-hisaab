<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Hard reset — wipes ALL business data so the app can be handed over clean
 * after testing. Users (developer + owner) and Settings (company name, logo)
 * are preserved. Requires typing "RESET" to confirm. Auth-gated, so only the
 * developer and owner (the only accounts) can reach it.
 */
class ResetController extends Controller
{
    /** Business tables wiped on reset. users + settings are intentionally kept. */
    private const TABLES = [
        'estimates',
        'work_entries',
        'wage_payments',
        'worker_advances',
        'material_purchases',
        'client_payments',
        'retention_releases',
        'other_expenses',
        'general_overheads',
        'audit_logs',
        'workers',
        'vendors',
        'projects',
    ];

    public function reset(Request $request): RedirectResponse
    {
        if ($request->input('confirm') !== 'RESET') {
            throw ValidationException::withMessages([
                'confirm' => 'Confirm karne ke liye exactly "RESET" likho (capital letters).',
            ]);
        }

        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        return redirect()->route('dashboard')
            ->with('status', 'Sab data reset ho gaya. Fresh start — sirf users aur settings bache hain.');
    }
}
