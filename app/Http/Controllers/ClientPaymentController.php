<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Models\Project;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
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
        $cp = ClientPayment::create($this->validatedData($request));

        return redirect()->route('money-in')->with('status', 'Client payment record ho gayi.')
            ->with('voucher_url', route('vouchers.client-payment', $cp))
            ->with('voucher_label', '🖨 Receipt');
    }

    public function edit(ClientPayment $clientPayment)
    {
        return view('money_in.edit', [
            'payment'  => $clientPayment,
            'projects' => $this->projectOptions($clientPayment),
        ]);
    }

    public function update(Request $request, ClientPayment $clientPayment)
    {
        // Wahi validation aur wahi hisaab jo store() karta hai, taake edit se
        // kabhi aisi row na bane jo add karte waqt mumkin hi na hoti.
        $clientPayment->update($this->validatedData($request));

        return redirect()->route('money-in')->with('status', 'Client payment durust ho gayi.')
            ->with('voucher_url', route('vouchers.client-payment', $clientPayment))
            ->with('voucher_label', '🖨 Receipt');
    }

    public function destroy(ClientPayment $clientPayment)
    {
        $clientPayment->delete();

        return back()->with('status', 'Payment hata di (soft delete).');
    }

    /** store() aur update() dono isi se guzarte hain: ek hi rule, ek hi hisaab. */
    private function validatedData(Request $request): array
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
        return [
            'project_id'           => $v['project_id'],
            'date'                 => $v['date'],
            'gross_amount_paisa'   => $gross,
            'retention_held_paisa' => 0,
            'net_received_paisa'   => $gross,
            'payment_method'       => $v['payment_method'],
            'reference'            => $v['reference'] ?? null,
            'is_mobilization'      => $isMob,
            'notes'                => $v['notes'] ?? null,
        ];
    }

    /**
     * Edit form ka project dropdown.
     *
     * Trap: project_id NOT NULL hai, magar project soft-delete ho sakta hai.
     * Aisi payment ki list me uska project aata hi nahi, to dropdown khali khulta
     * aur save karte waqt thekedaar ko majboori me koi doosra project chunna
     * parta, yani payment chup chaap kisi aur project par chali jati. Is liye
     * payment ka apna project, mita hua bhi ho, list me wapis daal dete hain
     * (saaf likha hua ke mita hua hai). Agar project ki row waqai gayab ho to
     * kuch preselect nahi hota aur 'required' use naya chunne par majboor kar
     * deta hai.
     */
    private function projectOptions(ClientPayment $payment): Collection
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);

        if ($payment->project_id && ! $projects->contains('id', $payment->project_id)) {
            $gone = Project::withTrashed()->find($payment->project_id, ['id', 'name']);

            if ($gone) {
                $gone->name = $gone->name . ' (mita hua project)';
                $projects->prepend($gone);
            }
        }

        return $projects;
    }
}
