@php
    $rateR = old('rate_per_unit', $purchase->rate_per_unit_paisa ? \App\Support\Money::toRupees($purchase->rate_per_unit_paisa) : '');
    $paidR = old('amount_paid', $purchase->amount_paid_paisa ? \App\Support\Money::toRupees($purchase->amount_paid_paisa) : '');
@endphp
<div x-data="{
        qty: {{ (float) old('qty', $purchase->qty ?? 1) }},
        rate: {{ (float) ($rateR ?: 0) }},
        paid: {{ (float) ($paidR ?: 0) }},
        get amount(){ return (this.qty * this.rate) || 0; },
        get balance(){ return Math.max(0, this.amount - this.paid); },
        fmt(n){ return '₨ ' + Number(n||0).toLocaleString('en-PK',{minimumFractionDigits:2,maximumFractionDigits:2}); }
     }">
    <x-card title="Purchase Details">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="project_id" value="Project (optional)" />
                <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— general / unassigned —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected((string)old('project_id', $purchase->project_id ?? ($selectedProject ?? ''))===(string)$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="vendor_id" value="Vendor (optional)" />
                <select id="vendor_id" name="vendor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— no vendor —</option>
                    @foreach($vendors as $vd)
                        <option value="{{ $vd->id }}" @selected((string)old('vendor_id', $purchase->vendor_id ?? '')===(string)$vd->id)>{{ $vd->name }} ({{ $vd->type }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="date" value="Date *" />
                <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', optional($purchase->date)->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
            </div>
            <div>
                <x-input-label for="item_name" value="Item *" />
                <x-text-input id="item_name" name="item_name" class="mt-1 block w-full" :value="old('item_name', $purchase->item_name)" required />
                <x-input-error :messages="$errors->get('item_name')" class="mt-1" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-input-label for="qty" value="Qty *" />
                    <x-text-input id="qty" name="qty" type="number" step="0.001" x-model.number="qty" class="mt-1 block w-full" :value="old('qty', $purchase->qty ?? 1)" required />
                </div>
                <div>
                    <x-input-label for="unit" value="Unit" />
                    <x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit', $purchase->unit)" placeholder="bag, kg…" />
                </div>
            </div>
            <x-money-input name="rate_per_unit" label="Rate / unit *" :value="$rateR" required x-model.number="rate" />
            <x-money-input name="amount_paid" label="Amount Paid (rest = udhaar)" :value="$paidR" x-model.number="paid" />
            <div>
                <x-input-label for="wastage_qty" value="Wastage Qty (optional)" />
                <x-text-input id="wastage_qty" name="wastage_qty" type="number" step="0.001" class="mt-1 block w-full" :value="old('wastage_qty', $purchase->wastage_qty)" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $purchase->notes) }}</textarea>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-3 gap-3 rounded-lg bg-gray-50 p-4 text-center text-sm">
            <div><div class="text-gray-500">Amount</div><div class="font-bold text-gray-900" x-text="fmt(amount)"></div></div>
            <div><div class="text-gray-500">Paid</div><div class="font-bold text-emerald-600" x-text="fmt(paid)"></div></div>
            <div><div class="text-gray-500">Balance (udhaar)</div><div class="font-bold text-red-600" x-text="fmt(balance)"></div></div>
        </div>
    </x-card>

    <div class="mt-5 flex items-center gap-3">
        <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ $submit ?? 'Save' }}</button>
        <a href="{{ route('materials.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
    </div>
</div>
