<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\Project;
use App\Models\RetentionRelease;
use App\Support\Money;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    public function index()
    {
        $payments = ClientPayment::with('project')->orderByDesc('date')->orderByDesc('id')
            ->paginate(20, ['*'], 'page')->withQueryString();
        $releases = RetentionRelease::with('project')->orderByDesc('date')->orderByDesc('id')
            ->paginate(10, ['*'], 'rpage')->withQueryString();

        return view('money_in.index', [
            'payments'         => $payments,
            'releases'         => $releases,
            'totalReceived'    => (int) ClientPayment::sum('net_received_paisa'),
            'totalRetention'   => (int) ClientPayment::sum('retention_held_paisa') - (int) RetentionRelease::sum('amount_paisa'),
            'projects'         => Project::orderBy('name')->get(['id', 'name', 'retention_percent']),
        ]);
    }

    public function create(Request $request)
    {
        return view('money_in.create', [
            'projects'        => Project::orderBy('name')->get(['id', 'name', 'retention_percent']),
            'selectedProject' => $request->integer('project'),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'project_id'      => ['required', 'exists:projects,id'],
            'date'            => ['required', 'date'],
            'gross_amount'    => ['required', 'numeric', 'min:0'],
            'retention_held'  => ['nullable', 'numeric', 'min:0'],
            'payment_method'  => ['required', 'in:cash,bank,cheque,online'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'is_mobilization' => ['nullable', 'boolean'],
            'notes'           => ['nullable', 'string'],
        ]);

        $gross = Money::toPaisa($v['gross_amount']);
        $isMob = (bool) ($v['is_mobilization'] ?? false);

        // Retention: explicit value if given, else project% (0 for mobilization).
        if ($request->filled('retention_held')) {
            $retention = Money::toPaisa($v['retention_held']);
        } elseif ($isMob) {
            $retention = 0;
        } else {
            $pct = (float) Project::find($v['project_id'])->retention_percent;
            $retention = (int) round($gross * $pct / 100);
        }
        $retention = min($retention, $gross); // never exceed gross

        $cp = ClientPayment::create([
            'project_id'           => $v['project_id'],
            'date'                 => $v['date'],
            'gross_amount_paisa'   => $gross,
            'retention_held_paisa' => $retention,
            'net_received_paisa'   => $gross - $retention,
            'payment_method'       => $v['payment_method'],
            'reference'            => $v['reference'] ?? null,
            'is_mobilization'      => $isMob,
            'notes'                => $v['notes'] ?? null,
        ]);

        return redirect()->route('money-in')->with('status', 'Client payment record ho gayi.')
            ->with('voucher_url', route('vouchers.client-payment', $cp))
            ->with('voucher_label', '🖨 Receipt');
    }

    public function destroy(ClientPayment $clientPayment)
    {
        $clientPayment->delete();

        return back()->with('status', 'Payment hata di (soft delete).');
    }
}
