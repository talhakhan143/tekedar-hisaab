<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        // Qism ka filter. Anjaan value (jaise ?type=bakwaas) ko khaamoshi se
        // chhod dete hain, warna list khaali dikhti aur maalik samjhta ke
        // vendor gum ho gaya.
        $type = (string) $request->query('type', '');
        if (! array_key_exists($type, Vendor::TYPES)) {
            $type = '';
        }

        // freight_sum ek hi query me aa jata hai, warna list ka har driver
        // apna kiraya alag query se nikalta (N+1).
        $vendors = Vendor::withSum('purchases as balance_sum', 'balance_due_paisa')
            ->withSum('trips as freight_sum', 'freight_paisa')
            ->withSum('driverPayments as driver_paid_sum', 'amount_paisa')
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->orderBy('name')->paginate(20)->withQueryString();

        // Company-wide payable, filter se bilkul alag: ye hamesha SAARE
        // vendors ka udhaar hai. Filter sirf list chhoti karta hai, maalik ka
        // kul udhaar usi se kam nahi hota.
        // Driver ka baqi kiraya bhi dena hai. balance_due_paisa me se us
        // purchase ka kiraya nikal chuka hota hai jis par driver laga ho, is
        // liye dono jama karne par dohri ginti nahi hoti.
        $totalPayable = (int) Vendor::sum('opening_balance_paisa')
            + (int) \App\Models\MaterialPurchase::sum('balance_due_paisa')
            + max(0, (int) \App\Models\MaterialPurchase::whereNotNull('driver_id')->sum('freight_paisa')
                - (int) \App\Models\DriverPayment::sum('amount_paisa'));

        return view('vendors.index', [
            'vendors'      => $vendors,
            'totalPayable' => $totalPayable,
            'filters'      => ['type' => $type],
        ]);
    }

    public function create()
    {
        return view('vendors.create', ['vendor' => new Vendor(['type' => 'supplier'])]);
    }

    public function store(Request $request)
    {
        Vendor::create($this->validated($request));

        return redirect()->route('vendors.index')->with('status', 'Vendor add ho gaya.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['purchases' => fn ($q) => $q->with('project')->orderBy('date')]);

        // Running balance ledger.
        $running = (int) $vendor->opening_balance_paisa;
        $ledger = [];
        if ($running != 0) {
            $ledger[] = ['date' => null, 'desc' => 'Opening balance', 'amount' => $running, 'paid' => 0, 'running' => $running];
        }
        foreach ($vendor->purchases as $p) {
            $running += (int) $p->balance_due_paisa;
            $ledger[] = [
                'date'    => $p->date,
                'desc'    => $p->item_name . ($p->project ? ' · ' . $p->project->name : ''),
                'amount'  => (int) $p->amount_paisa,
                'paid'    => (int) $p->amount_paid_paisa,
                'running' => $running,
            ];
        }

        return view('vendors.show', [
            'vendor'  => $vendor,
            'ledger'  => $ledger,
            'payable' => $vendor->payablePaisa(),
        ]);
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $vendor->update($this->validated($request));

        return redirect()->route('vendors.show', $vendor)->with('status', 'Vendor update ho gaya.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendors.index')->with('status', 'Vendor hata diya.');
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            // Qismein Vendor::TYPES se aati hain (supplier / subcontractor /
            // driver). Yahan hardcode likha tha, is liye naya 'driver' reject
            // ho jata. Ab list ek hi jagah hai.
            'type'            => ['required', Rule::in(array_keys(Vendor::TYPES))],
            'phone'           => ['nullable', 'string', 'max:50'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes'           => ['nullable', 'string'],
        ]);

        return [
            'name'                  => $v['name'],
            'type'                  => $v['type'],
            'phone'                 => $v['phone'] ?? null,
            'opening_balance_paisa' => Money::toPaisa($v['opening_balance'] ?? 0),
            'notes'                 => $v['notes'] ?? null,
        ];
    }
}
