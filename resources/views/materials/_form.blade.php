@php
    $rateR = old('rate_per_unit', $purchase->rate_per_unit_paisa ? \App\Support\Money::toRupees($purchase->rate_per_unit_paisa) : '');
    $paidR = old('amount_paid', $purchase->amount_paid_paisa ? \App\Support\Money::toRupees($purchase->amount_paid_paisa) : '');

    /*
     * Maal par laga kiraya / mazdoori / deegar kharcha. Ye teen khane EDIT form me
     * bhi laazmi render hone chahiye: edit poori row wapis isi validation se
     * guzarti hai, to agar khane gayab hon to har edit in charges ko khamoshi se
     * zero kar dega aur bill chhota ho jayega. Is liye prefill bhi rupees me hai,
     * paisa me nahi (jaise rate/paid hote hain).
     *
     * Khali value '' hi rehti hai, 0 nahi, warna teen optional khane bila wajah
     * "0" likhe nazar aate hain.
     */
    $freightR = old('freight',       $purchase->freight_paisa       ? \App\Support\Money::toRupees($purchase->freight_paisa) : '');
    $labourR  = old('labour_charge', $purchase->labour_paisa        ? \App\Support\Money::toRupees($purchase->labour_paisa) : '');
    $otherR   = old('other_charges', $purchase->other_charges_paisa ? \App\Support\Money::toRupees($purchase->other_charges_paisa) : '');
@endphp
<div x-data="{
        qty: {{ (float) old('qty', $purchase->qty ?? 1) }},
        rate: {{ (float) ($rateR ?: 0) }},
        paid: {{ (float) ($paidR ?: 0) }},
        freight: @js($freightR === '' ? '' : (float) $freightR),
        labour: @js($labourR === '' ? '' : (float) $labourR),
        other: @js($otherR === '' ? '' : (float) $otherR),
        num(v){ const n = Number(v); return isFinite(n) ? n : 0; },
        get goods(){ return (this.qty * this.rate) || 0; },
        get extras(){ return this.num(this.freight) + this.num(this.labour) + this.num(this.other); },
        get total(){ return this.goods + this.extras; },
        get balance(){ return Math.max(0, this.total - this.paid); },
        fmt(n){ return '₨ ' + Number(n||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); },
        qtyText(){ return Number(this.qty||0).toLocaleString('en-IN',{maximumFractionDigits:3}); }
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

            <div class="sm:col-span-2">
                <div class="rounded-lg border border-dashed border-gray-300 bg-amber-50 p-4">
                    <div class="text-sm font-semibold text-gray-700">Maal ke saath lage charges (optional)</div>
                    <p class="mt-0.5 text-xs text-gray-500">Jo kharcha isi bill par laga ho sirf wahi likho. Khali chhor do to bill sirf maal ka banega.</p>
                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-money-input name="freight" label="Kiraya (کرایہ)" :value="$freightR"
                                       help="Truck ya dala ka kiraya. Na laga ho to khali chhor do." x-model.number="freight" />
                        <x-money-input name="labour_charge" label="Mazdoori (مزدوری)" :value="$labourR"
                                       help="Maal utarne charhane ki mazdoori. Na di ho to khali chhor do." x-model.number="labour" />
                        <x-money-input name="other_charges" label="Deegar charges (دیگر)" :value="$otherR"
                                       help="Koi aur kharcha, jaise tolai ya tax. Na ho to khali chhor do." x-model.number="other" />
                    </div>
                </div>
            </div>

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

        {{-- Save se pehle poora hisaab samne: maal, phir upar lage charges, phir kul bill. --}}
        <div class="mt-5 rounded-lg bg-gray-50 p-4 text-sm">
            <div class="space-y-1">
                <div class="flex justify-between">
                    <span class="text-gray-500">Maal (<span x-text="qtyText()"></span> × <span x-text="fmt(rate)"></span>)</span>
                    <span class="font-semibold text-gray-900" x-text="fmt(goods)"></span>
                </div>
                <div class="flex justify-between" x-show="num(freight) > 0" x-cloak>
                    <span class="text-gray-500">+ Kiraya (کرایہ)</span>
                    <span class="font-semibold text-gray-900" x-text="fmt(num(freight))"></span>
                </div>
                <div class="flex justify-between" x-show="num(labour) > 0" x-cloak>
                    <span class="text-gray-500">+ Mazdoori (مزدوری)</span>
                    <span class="font-semibold text-gray-900" x-text="fmt(num(labour))"></span>
                </div>
                <div class="flex justify-between" x-show="num(other) > 0" x-cloak>
                    <span class="text-gray-500">+ Deegar (دیگر)</span>
                    <span class="font-semibold text-gray-900" x-text="fmt(num(other))"></span>
                </div>
                <div class="flex justify-between border-t border-gray-300 pt-2 text-base">
                    <span class="font-bold text-gray-900">Kul bill (کل بل)</span>
                    <span class="font-bold text-gray-900" x-text="fmt(total)"></span>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 border-t border-dashed border-gray-300 pt-3 text-center">
                <div><div class="text-gray-500">Paid (دیا)</div><div class="font-bold text-emerald-600" x-text="fmt(paid)"></div></div>
                <div><div class="text-gray-500">Balance (udhaar)</div><div class="font-bold text-red-600" x-text="fmt(balance)"></div></div>
            </div>
        </div>
    </x-card>

    <div class="mt-5 flex items-center gap-3">
        <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ $submit ?? 'Save' }}</button>
        <a href="{{ route('materials.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
    </div>
</div>
