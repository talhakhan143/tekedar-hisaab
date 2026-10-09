<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\Project;
use App\Support\Money;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    public function index()
    {
        $payments = ClientPayment::with('project')->orderByDesc('date')->orderByDesc('id')
            ->paginate(20, ['*'], 'page')->withQueryString();
        return view('money_in.index', [
            'payments'      => $payments,
            'totalReceived' => (int) ClientPayment::sum('net_received_paisa'),
            'projects'      => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        return view('money_in.create', [
            'projects'        => Project::orderBy('name')->get(['id', 'name']),
            'selectedProject' => $request->integer('project'),
        ]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'project_id'      => ['required', 'exists:projects,id'],
            'date'            => ['required', 'date'],
            'gross_amount'    => ['required', 'numeric', 'min:0'],
            'payment_method'  => ['required', 'in:cash,bank,cheque,online'],
            'reference'       => ['nullable', 'string', 'max:100'],
            'is_mobilization' => ['nullable', 'boolean'],
            'notes'           => ['nullable', 'string'],
        ]);

        $gross = Money::toPaisa($v['gross_amount']);
        $isMob = (bool) ($v['is_mobilization'] ?? false);

        // Client jitna de, utna hi mila. Retention ka concept hata dia gaya hai,
        // is liye held hamesha 0 aur net hamesha gross ke barabar.
        $cp = ClientPayment::create([
            'project_id'           => $v['project_id'],
            'date'                 => $v['date'],
            'gross_amount_paisa'   => $gross,
            'retention_held_paisa' => 0,
            'net_received_paisa'   => $gross,
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
