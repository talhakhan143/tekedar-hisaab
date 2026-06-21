<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Support\Money;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::withSum('purchases as balance_sum', 'balance_due_paisa')
            ->orderBy('name')->get();

        $totalPayable = $vendors->sum(fn ($v) => (int) $v->opening_balance_paisa + (int) ($v->balance_sum ?? 0));

        return view('vendors.index', compact('vendors', 'totalPayable'));
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
            'type'            => ['required', 'in:supplier,subcontractor'],
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
