<x-app-layout>
    <x-slot name="title">{{ $project->name }}</x-slot>
    <x-slot name="header">{{ $project->name }}</x-slot>

    @php
        $contract  = (int) $project->contract_value_paisa;
        $received  = $f->grossBilledPaisa();
        $balance   = max(0, $contract - $received);
        $expense   = $f->totalAccruedCostPaisa();
        $profit    = $contract - $expense;
        $pie       = $f->costPie();
        $workerDueTotal = $projectWorkers->sum(fn($r)=>max(0, $r['earned'] - $r['paid'] - ($r['advnet']??0)));
        $payable   = $workerDueTotal + $f->vendorPayablePaisa();
        $receivable = $balance;
    @endphp

    <div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'overview' }">

        {{-- Header row --}}
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <x-badge :color="$project->statusColor()">{{ ucfirst(str_replace('_',' ',$project->status)) }}</x-badge>
            <span class="text-sm text-gray-500">{{ $project->client_name }} · {{ $project->client_phone }}</span>
            <span class="flex-1"></span>
            <a href="{{ route('projects.edit', $project) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Edit (تبدیلی)</a>
        </div>

        {{-- Tabs --}}
        @php
            $tabs = [
                ['overview',   'Overview (خلاصہ)'],
                ['money-in',   'Client Payments (وصولی)'],
                ['materials',  'Materials (مٹیریل)'],
                ['attendance', 'Labour / Attendance (مزدوری)'],
                ['adjustment', 'Adjustment (کٹوتی/بونس)'],
                ['expenses',   'Other Expenses (اخراجات)'],
                ['ledger',     'Lena / Dena (لین دین)'],
                ['estimates',  'Estimate (تخمینہ)'],
            ];
        @endphp
        <div class="mb-5 flex flex-wrap gap-1 border-b border-gray-200">
            @foreach($tabs as [$key, $label])
                <button @click="tab='{{ $key }}'"
                    :class="tab==='{{ $key }}' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium">{{ $label }}</button>
            @endforeach
        </div>

        {{-- ================= OVERVIEW ================= --}}
        <div x-show="tab==='overview'" x-cloak>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
                <x-stat label="Contract (ٹھیکہ)" :value="\App\Support\Money::short($contract)" color="text-sky-600" />
                <x-stat label="Received (مل گیا)" :value="\App\Support\Money::short($received)" color="text-emerald-600" />
                <x-stat label="Balance (باقی)" :value="\App\Support\Money::short($balance)" color="text-amber-600" sub="client se lena" />
                <x-stat label="Expense (خرچ)" :value="\App\Support\Money::short($expense)" color="text-rose-600" />
                <x-stat label="Profit (منافع)" :value="\App\Support\Money::short($profit)" :color="$profit < 0 ? 'text-red-600' : 'text-indigo-600'" sub="contract − kharch" />
            </div>

            {{-- Lena / Dena quick cards (click -> ledger tab) --}}
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <button type="button" @click="tab='ledger'" class="rounded-xl bg-emerald-50 p-5 text-left ring-1 ring-emerald-200 hover:ring-emerald-400">
                    <div class="text-sm font-medium text-emerald-700">📥 Lena hai — Receivable (وصولی)</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-700">@money($receivable)</div>
                    <div class="mt-1 text-xs text-emerald-600/70">Client se baqi · click → Lena/Dena</div>
                </button>
                <button type="button" @click="tab='ledger'" class="rounded-xl bg-rose-50 p-5 text-left ring-1 ring-rose-200 hover:ring-rose-400">
                    <div class="text-sm font-medium text-rose-700">📤 Dena hai — Payable (واجبات)</div>
                    <div class="mt-1 text-2xl font-bold text-rose-700">@money($payable)</div>
                    <div class="mt-1 text-xs text-rose-600/70">Mazdoor @money($workerDueTotal) + Vendor @money($f->vendorPayablePaisa())</div>
                </button>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-card title="Contract (ٹھیکہ تفصیل)">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd class="font-medium">{{ $project->contractTypeLabel() }}</dd></div>
                        @if($project->pricing_mode === 'per_sqft')
                            <div class="flex justify-between"><dt class="text-gray-500">Area</dt><dd class="font-medium">{{ number_format($project->covered_area_sqft) }} sqft</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Rate/sqft</dt><dd class="font-medium">@money($project->rate_per_sqft_paisa)</dd></div>
                        @endif
                        <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">Contract Value</dt><dd class="font-bold">@money($contract)</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Start</dt><dd class="font-medium">{{ optional($project->start_date)->format('d-m-Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Expected End</dt><dd class="font-medium">{{ optional($project->expected_end_date)->format('d-m-Y') ?? '—' }}</dd></div>
                    </dl>
                </x-card>
                <x-card title="Kharch breakdown (لاگت)">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Material (مٹیریل)</dt><dd class="font-medium">@money($pie['material'])</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Labour (مزدوری)</dt><dd class="font-medium">@money($pie['labour'])</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Other (دیگر)</dt><dd class="font-medium">@money($pie['other'])</dd></div>
                        <div class="flex justify-between border-t pt-2"><dt class="font-medium text-gray-700">Total Kharch</dt><dd class="font-bold text-rose-600">@money($expense)</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Vendor udhaar baqi</dt><dd class="font-medium text-red-600">@money($f->vendorPayablePaisa())</dd></div>
                    </dl>
                </x-card>
                <x-card title="Estimate vs Actual (تخمینہ بمقابلہ اصل)" class="!p-0">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Cat</th><th class="px-3 py-2 text-right">Est.</th><th class="px-3 py-2 text-right">Actual</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($variance as $vv)@if($vv['estimate']>0 || $vv['actual']>0)
                                <tr class="{{ $vv['over'] ? 'bg-red-50' : '' }}"><td class="px-3 py-2 capitalize">{{ $vv['category'] }}</td><td class="px-3 py-2 text-right text-gray-500">@money($vv['estimate'])</td><td class="px-3 py-2 text-right font-medium {{ $vv['over'] ? 'text-red-600' : '' }}">@money($vv['actual'])</td></tr>
                            @endif @endforeach
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= CLIENT PAYMENTS ================= --}}
        <div x-show="tab==='money-in'" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-card title="Client ne diya (وصولی شامل کریں)">
                    <form method="POST" action="{{ route('projects.payments.store', $project) }}" class="space-y-3">
                        @csrf
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <x-money-input name="amount" label="Amount (رقم)" required />
                        <select name="payment_method" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach(['cash','bank','cheque','online'] as $m)<option value="{{ $m }}">{{ ucfirst($m) }}</option>@endforeach
                        </select>
                        <label class="flex items-center gap-2 text-xs text-gray-600"><input type="hidden" name="is_advance" value="0"><input type="checkbox" name="is_advance" value="1" class="rounded border-gray-300 text-emerald-600"> Advance / peshgi (kaam se pehle)</label>
                        <input type="text" name="notes" placeholder="Note (optional)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add (شامل کریں)</button>
                    </form>
                    <div class="mt-4 space-y-1 rounded-lg bg-gray-50 p-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Total Received</span><span class="font-bold text-emerald-600">@money($received)</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Balance (baqi)</span><span class="font-bold text-amber-600">@money($balance)</span></div>
                    </div>
                </x-card>
            </div>
            <div class="lg:col-span-2">
                <x-card title="Payments (وصولیاں)" class="!p-0">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Note</th><th class="px-4 py-3 text-right">Amount</th><th></th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($project->clientPayments->sortByDesc('date') as $pay)
                                <tr>
                                    <td class="px-4 py-2.5">{{ $pay->date->format('d-m-Y') }}</td>
                                    <td class="px-4 py-2.5 capitalize">{{ $pay->payment_method }} @if($pay->is_mobilization)<x-badge color="sky">Advance</x-badge>@endif</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $pay->notes }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-emerald-600">@money($pay->gross_amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-right whitespace-nowrap"><a href="{{ route('vouchers.client-payment', $pay) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800" title="Print receipt">🖨</a><form method="POST" action="{{ route('client-payments.destroy', $pay) }}" class="ml-1 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                                </tr>
                            @empty<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Koi payment nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= MATERIALS ================= --}}
        <div x-show="tab==='materials'" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-card title="Maal khareeda (مٹیریل شامل کریں)">
                    <form method="POST" action="{{ route('projects.materials.store', $project) }}" class="space-y-3"
                          x-data="{ qty:1, rate:0, paid:0, get amt(){return (this.qty*this.rate)||0}, fmt(n){return '₨ '+Math.round(n||0).toLocaleString('en-PK')} }">
                        @csrf
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <input type="text" name="item_name" placeholder="Item (cement, sarya…)" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" step="0.001" name="qty" x-model.number="qty" placeholder="Qty" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <input type="text" name="unit" placeholder="Unit" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <x-money-input name="rate_per_unit" label="Rate / unit (ریٹ)" required x-model.number="rate" />
                        <select name="vendor_id" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— supplier (optional) —</option>
                            @foreach($vendors->where('type','supplier') as $vd)<option value="{{ $vd->id }}">{{ $vd->name }}</option>@endforeach
                        </select>
                        <x-money-input name="amount_paid" label="Paid (jo diya — baqi udhaar)" x-model.number="paid" />
                        <div class="rounded bg-gray-50 p-2 text-center text-xs text-gray-500">Amount: <span class="font-bold text-gray-800" x-text="fmt(amt)"></span> · Udhaar: <span class="font-bold text-red-600" x-text="fmt(Math.max(0,amt-paid))"></span></div>
                        <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add (شامل کریں)</button>
                    </form>
                </x-card>
            </div>
            <div class="lg:col-span-2">
                <x-card title="Material (مٹیریل)" class="!p-0">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Vendor</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Udhaar</th><th></th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($project->materialPurchases->filter(fn($p)=>!$p->vendor || $p->vendor->type==='supplier')->sortByDesc('date') as $pur)
                                <tr>
                                    <td class="px-4 py-2.5">{{ $pur->date->format('d-m-Y') }}</td>
                                    <td class="px-4 py-2.5 font-medium text-gray-700">{{ $pur->item_name }}<div class="text-xs text-gray-400">{{ rtrim(rtrim($pur->qty,'0'),'.') }} {{ $pur->unit }}</div></td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $pur->vendor->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-right">@money($pur->amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-right {{ $pur->balance_due_paisa>0?'text-red-600':'text-gray-400' }}">@money($pur->balance_due_paisa)</td>
                                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                        @if($pur->balance_due_paisa > 0)
                                            <form method="POST" action="{{ route('materials.pay', $pur) }}" class="inline-flex items-center gap-1">
                                                @csrf
                                                <input type="number" step="0.01" name="amount" value="{{ \App\Support\Money::toRupees($pur->balance_due_paisa) }}" class="w-24 rounded border-gray-300 px-2 py-1 text-xs" title="udhaar pay">
                                                <button class="rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-700">Pay</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('vouchers.material', $pur) }}" target="_blank" class="ml-1 text-emerald-600 hover:text-emerald-800" title="Print invoice">🖨</a>
                                        <form method="POST" action="{{ route('materials.destroy', $pur) }}" class="ml-1 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form>
                                    </td>
                                </tr>
                            @empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi material nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= SUB-CONTRACTOR (THEKA) ================= --}}
        @php
            $subDeals = $project->materialPurchases->filter(fn($p)=>$p->vendor && $p->vendor->type==='subcontractor')->sortByDesc('date');
            $subAgreed = (int) $subDeals->sum('amount_paisa');
            $subPaid   = (int) $subDeals->sum('amount_paid_paisa');
            $subBaqi   = (int) $subDeals->sum('balance_due_paisa');
        @endphp
        <div x-show="tab==='subcontractor'" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-card title="Theka do (ٹھیکہ پر کام)">
                    <p class="mb-3 text-xs text-gray-500">Kisi sub-contractor ko kaam theke pe diya — agreed amount, advance jo diya. Baqi "Lena/Dena" me dena ban jata hai.</p>
                    <form method="POST" action="{{ route('projects.subcontractor.store', $project) }}" class="space-y-3"
                          x-data="{ mode:'old' }">
                        @csrf
                        <div class="flex gap-2 text-xs">
                            <label class="flex items-center gap-1"><input type="radio" value="old" x-model="mode" class="text-emerald-600"> Purana</label>
                            <label class="flex items-center gap-1"><input type="radio" value="new" x-model="mode" class="text-emerald-600"> Naya</label>
                        </div>
                        <select name="vendor_id" x-show="mode==='old'" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— sub-contractor chuno —</option>
                            @foreach($vendors->where('type','subcontractor') as $vd)<option value="{{ $vd->id }}">{{ $vd->name }}</option>@endforeach
                        </select>
                        <input type="text" name="new_name" x-show="mode==='new'" placeholder="Naya sub-contractor naam" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <input type="text" name="work" placeholder="Kaam (plaster, tile work…)" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <x-money-input name="agreed" label="Theka amount (طے شدہ رقم)" required />
                        <x-money-input name="paid" label="Advance jo diya (optional)" />
                        <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add Theka (شامل کریں)</button>
                    </form>
                    <div class="mt-4 space-y-1 rounded-lg bg-gray-50 p-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Total theka</span><span class="font-bold">@money($subAgreed)</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Diya</span><span class="font-bold text-emerald-600">@money($subPaid)</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Baqi (dena)</span><span class="font-bold text-red-600">@money($subBaqi)</span></div>
                    </div>
                </x-card>
            </div>
            <div class="lg:col-span-2">
                <x-card title="Sub-contractor theke (ٹھیکے)" class="!p-0">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Sub-contractor</th><th class="px-4 py-3">Kaam</th><th class="px-4 py-3 text-right">Theka</th><th class="px-4 py-3 text-right">Baqi</th><th></th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($subDeals as $sd)
                                <tr>
                                    <td class="px-4 py-2.5">{{ $sd->date->format('d-m-Y') }}</td>
                                    <td class="px-4 py-2.5 font-medium text-gray-700">{{ $sd->vendor->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $sd->item_name }}</td>
                                    <td class="px-4 py-2.5 text-right">@money($sd->amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-right {{ $sd->balance_due_paisa>0?'text-red-600':'text-gray-400' }}">@money($sd->balance_due_paisa)</td>
                                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                        @if($sd->balance_due_paisa > 0)
                                            <form method="POST" action="{{ route('materials.pay', $sd) }}" class="inline-flex items-center gap-1">
                                                @csrf
                                                <input type="number" step="0.01" name="amount" value="{{ \App\Support\Money::toRupees($sd->balance_due_paisa) }}" class="w-24 rounded border-gray-300 px-2 py-1 text-xs" title="baqi pay">
                                                <button class="rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-700">Pay</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('vouchers.material', $sd) }}" target="_blank" class="ml-1 text-emerald-600 hover:text-emerald-800" title="Print invoice">🖨</a>
                                        <form method="POST" action="{{ route('materials.destroy', $sd) }}" class="ml-1 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form>
                                    </td>
                                </tr>
                            @empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi theka nahi. Side se add karo.</td></tr>@endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= ATTENDANCE / LABOUR ================= --}}
        <div x-show="tab==='attendance'" x-cloak
             x-data="attendanceHub({
                marked: {{ \Illuminate\Support\Js::from($attMarked) }},
                today: '{{ $attToday }}',
                curYear: {{ $attCurYear }},
                curMon: '{{ $attCurMon }}',
                years: {{ \Illuminate\Support\Js::from(array_values($attYears)) }}
             })">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Worker list -> Haazri button per worker --}}
                <div class="lg:col-span-2 space-y-6">
                    <x-card title="Mazdoor — Haazri lagao (حاضری)" class="!p-0">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Worker</th><th class="px-4 py-3">Kaam</th><th class="px-4 py-3 text-right">Dihaadi</th><th class="px-4 py-3 text-right">Haazri</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($attWorkers as $w)
                                    <tr>
                                        <td class="px-4 py-2.5 font-medium text-gray-700">{{ $w->name }} <span class="text-xs font-normal text-gray-400">#{{ $w->id }}</span></td>
                                        <td class="px-4 py-2.5 capitalize text-gray-500">{{ $w->role }}</td>
                                        <td class="px-4 py-2.5 text-right">@money($w->default_wage_paisa)</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <button type="button" @click="openModal({{ $w->id }}, @js($w->name))"
                                                class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">📅 Haazri lagao</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Koi mazdoor nahi. Side se "Naya Mazdoor" add karo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </x-card>

                    <x-card title="Is project ke mazdoor — hisaab (لیبر)" class="!p-0">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Worker</th><th class="px-4 py-3 text-right">Days</th><th class="px-4 py-3 text-right">Earned</th><th class="px-4 py-3 text-right">Paid</th><th class="px-4 py-3 text-right">Baqi / Advance</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($projectWorkers as $row)
                                    @php $net = $row['earned'] - $row['paid'] - ($row['advnet'] ?? 0); @endphp
                                    <tr><td class="px-4 py-2.5"><a href="{{ route('workers.show', $row['worker']) }}" class="font-medium text-emerald-700 hover:underline">{{ $row['worker']->name }}</a><span class="ml-1 text-xs text-gray-400">#{{ $row['worker']->id }} · {{ $row['worker']->role }}</span><a href="{{ route('vouchers.worker-statement', ['worker' => $row['worker'], 'project' => $project->id]) }}" target="_blank" class="ml-2 text-emerald-600 hover:text-emerald-800" title="Print statement">🖨</a></td>
                                    <td class="px-4 py-2.5 text-right">{{ rtrim(rtrim(number_format($row['days'],1),'0'),'.') }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium">@money($row['earned'])</td>
                                    <td class="px-4 py-2.5 text-right text-emerald-600">@money($row['paid'])</td>
                                    <td class="px-4 py-2.5 text-right font-semibold">
                                        @if($net > 0)<span class="text-red-600">@money($net)</span><span class="ml-1 text-xs font-normal text-gray-400">dena</span>
                                        @elseif($net < 0)<span class="text-amber-600">@money(-$net)</span><span class="ml-1 text-xs font-normal text-gray-400">advance</span>
                                        @else<span class="text-gray-400">—</span>@endif
                                    </td></tr>
                                @empty<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Abhi koi haazri nahi.</td></tr>@endforelse
                            </tbody>
                            @if($projectWorkers->count())
                                @php
                                    $totEarned = $projectWorkers->sum('earned');
                                    $totPaid   = $projectWorkers->sum('paid');
                                    // Dena and advance kept SEPARATE — alag bandon ka cancel nahi hota.
                                    $totDena    = $projectWorkers->sum(fn($r) => max(0, $r['earned'] - $r['paid'] - ($r['advnet'] ?? 0)));
                                    $totAdvance = $projectWorkers->sum(fn($r) => max(0, -($r['earned'] - $r['paid'] - ($r['advnet'] ?? 0))));
                                @endphp
                                <tfoot class="bg-gray-50 font-semibold"><tr><td class="px-4 py-3">Total</td><td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format($projectWorkers->sum('days'),1),'0'),'.') }}</td><td class="px-4 py-3 text-right">@money($totEarned)</td><td class="px-4 py-3 text-right text-emerald-600">@money($totPaid)</td>
                                <td class="px-4 py-3 text-right">
                                    @if($totDena > 0)<div><span class="text-red-600">@money($totDena)</span> <span class="text-xs font-normal text-gray-400">dena</span></div>@endif
                                    @if($totAdvance > 0)<div><span class="text-amber-600">@money($totAdvance)</span> <span class="text-xs font-normal text-gray-400">advance</span></div>@endif
                                    @if($totDena <= 0 && $totAdvance <= 0)<span class="text-gray-400">—</span>@endif
                                </td></tr></tfoot>
                            @endif
                        </table>
                    </x-card>
                </div>

                <div class="space-y-6">
                    <x-card title="Naya Mazdoor (نیا مزدور)">
                        <form method="POST" action="{{ route('attendance.quick-worker') }}" class="space-y-3">
                            @csrf
                            <input type="text" name="name" placeholder="Naam" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <select name="role" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach($workerRoles as $r)<option value="{{ $r }}">{{ ucfirst($r) }}</option>@endforeach
                            </select>
                            <p class="text-[11px] text-gray-400">Role list <a href="{{ route('settings') }}" class="text-emerald-600 hover:underline">Settings</a> se edit hoti.</p>
                            <x-money-input name="default_wage" label="Dihaadi (روزانہ)" required />
                            <button class="w-full rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-700">Add Worker (شامل)</button>
                        </form>
                    </x-card>
                    <x-card title="Mazdoori do (ادائیگی)">
                        @php
                            // Wahi hisaab jo WorkLedgerController save karte waqt lagata hai.
                            $wageDuesJson = collect($wageDues)->map(fn ($d) => [
                                'here'        => $d['here'],
                                'othersTotal' => $d['others_total'],
                                'total'       => $d['total'],
                                'others'      => $d['others'],
                            ])->toJson();
                        @endphp
                        <form method="POST" action="{{ route('projects.wage-payments.store', $project) }}" class="space-y-3"
                              x-data="{
                                dues: {{ $wageDuesJson }},
                                worker: '{{ old('worker_id') }}',
                                amount: '{{ old('amount') }}',
                                allProjects: {{ old('all_projects') ? 'true' : 'false' }},
                                get d() { return this.worker === '' ? null : (this.dues[this.worker] ?? {here: 0, othersTotal: 0, total: 0, others: []}); },
                                get target() { return this.d === null ? 0 : (this.allProjects ? this.d.total : this.d.here); },
                                fmt(paisa) { return '₨ ' + Number(Math.abs(paisa) / 100).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
                                payAll() { if (this.target > 0) { this.amount = (this.target / 100).toFixed(2); } }
                              }"
                              x-init="$watch('worker', () => { amount = '' })">
                            @csrf
                            <select name="worker_id" x-model="worker" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">— worker chuno —</option>
                                @foreach($allWorkers as $w)
                                    @php $d = $wageDues[$w->id]; @endphp
                                    <option value="{{ $w->id }}">#{{ $w->id }} — {{ $w->name }} ({{ $w->role }}) ·
                                        @if($d['here'] > 0) is project {{ \App\Support\Money::format($d['here'], true, false) }}
                                        @elseif($d['total'] > 0) is project saaf
                                        @elseif($d['total'] < 0) advance {{ \App\Support\Money::format(-$d['total'], true, false) }}
                                        @else hisaab saaf @endif
                                        @if($d['others_total'] > 0) · baqi projects {{ \App\Support\Money::format($d['others_total'], true, false) }} @endif
                                    </option>
                                @endforeach
                            </select>

                            {{-- Chunte hi saaf dikh jaye ke kahan kitna banta hai. --}}
                            <template x-if="worker !== ''">
                                <div class="space-y-2 rounded-lg px-3 py-2 text-sm"
                                     :class="target > 0 ? 'bg-red-50 ring-1 ring-red-100' : 'bg-gray-50 ring-1 ring-gray-100'">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs" :class="d.here > 0 ? 'text-red-700' : 'text-gray-500'">Is project ka (اس پروجیکٹ کا)</span>
                                        <span class="font-bold" :class="d.here > 0 ? 'text-red-700' : 'text-gray-400'" x-text="d.here > 0 ? fmt(d.here) : '—'"></span>
                                    </div>

                                    <template x-if="d.othersTotal > 0">
                                        <div class="space-y-1 border-t border-red-100 pt-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs text-amber-700">Doosre projects ka (دوسرے پروجیکٹ)</span>
                                                <span class="font-bold text-amber-700" x-text="fmt(d.othersTotal)"></span>
                                            </div>
                                            <template x-for="row in d.others" :key="row.name">
                                                <div class="flex items-center justify-between gap-2 pl-2 text-[11px] text-gray-500">
                                                    <span x-text="row.name"></span>
                                                    <span x-text="fmt(row.due)"></span>
                                                </div>
                                            </template>
                                            <label class="mt-1 flex items-start gap-2 rounded-md bg-white/70 px-2 py-1.5 text-xs text-gray-700">
                                                <input type="hidden" name="all_projects" value="0">
                                                <input type="checkbox" name="all_projects" value="1" x-model="allProjects" @change="amount = ''"
                                                       class="mt-0.5 rounded border-gray-300 text-emerald-600">
                                                <span>Sab projects ke dues ek sath bhar do.
                                                    <span class="text-gray-400">Paisa phir bhi har project me alag alag record hoga.</span>
                                                </span>
                                            </label>
                                        </div>
                                    </template>

                                    <div class="flex items-center justify-between gap-2 border-t pt-2"
                                         :class="target > 0 ? 'border-red-100' : 'border-gray-200'">
                                        <span class="text-xs font-medium" :class="target > 0 ? 'text-red-800' : 'text-gray-500'"
                                              x-text="allProjects ? 'Ab dena hai, sab projects' : 'Ab dena hai, sirf ye project'"></span>
                                        <span class="font-bold" :class="target > 0 ? 'text-red-800' : 'text-gray-400'"
                                              x-text="target > 0 ? fmt(target) : 'Hisaab saaf'"></span>
                                    </div>

                                    <template x-if="target > 0">
                                        <button type="button" @click="payAll()"
                                                class="text-xs font-semibold text-red-700 underline hover:text-red-900">Poora dedo</button>
                                    </template>
                                </div>
                            </template>

                            <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <x-money-input name="amount" label="Amount (رقم)" required x-model="amount" />
                            <label class="flex items-center gap-2 text-xs text-gray-600"><input type="hidden" name="override" value="0"><input type="checkbox" name="override" value="1" class="rounded border-gray-300 text-emerald-600"> Override (payable se zyada)</label>
                            <x-input-error :messages="$errors->get('amount')" />
                            <button class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Pay (ادائیگی)</button>
                        </form>
                    </x-card>
                </div>
            </div>

            {{-- ===== Haazri modal (per worker, calendar) ===== --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4"
                 @keydown.escape.window="close()">
                <div class="my-8 w-full max-w-3xl rounded-2xl bg-white shadow-xl" @click.outside="close()">
                    <form method="POST" action="{{ route('projects.attendance.bulk', $project) }}">
                        @csrf
                        <input type="hidden" name="worker_id" :value="wId">
                        <input type="hidden" name="status" :value="status">
                        <input type="hidden" name="dates" :value="selDates">

                        <div class="flex items-center justify-between border-b border-gray-100 p-5">
                            <h3 class="text-lg font-bold text-gray-900">Haazri — <span x-text="wName"></span></h3>
                            <button type="button" @click="close()" class="text-xl text-gray-400 hover:text-gray-600">✕</button>
                        </div>

                        <div class="p-5">
                            <div class="mb-4 flex flex-wrap gap-4">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Saal (year)</label>
                                    <select x-model.number="year" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        @foreach($attYears as $yr)<option value="{{ $yr }}">{{ $yr }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Mahina (month)</label>
                                    <select x-model="month" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        @foreach($attMonthNames as $mv => $ml)<option value="{{ $mv }}">{{ $ml }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Status (kya lagana)</label>
                                    <select x-model="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="present">Present (poora din)</option>
                                        <option value="half">Half (aadha din)</option>
                                        <option value="absent">Absent (ghair-haazir)</option>
                                    </select>
                                </div>
                            </div>

                            <p class="mb-3 text-sm text-gray-500">Khaali din pe click karke select karein (ek ya kayi), phir niche "Mark" dabayein. Jis din pehle se haazri lag chuki wo <b>locked</b> hai — galti theek karni ho to Adjustments se.</p>

                            <div class="mb-1 grid grid-cols-7 gap-1 text-center text-xs font-semibold text-gray-500">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div class="text-amber-600">Fri</div><div>Sat</div>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(c, i) in cells" :key="i">
                                    <div>
                                        <template x-if="c.blank"><div class="h-16"></div></template>
                                        <template x-if="!c.blank">
                                            <button type="button" @click="toggle(c.day)"
                                                :disabled="isLocked(c.day) || isFuture(c.day)"
                                                :class="cellClass(c.day)"
                                                class="flex h-16 w-full flex-col items-center justify-center rounded-lg border text-sm transition">
                                                <span class="font-bold" x-text="c.day"></span>
                                                <span class="text-[10px] opacity-70" x-text="weekdayLabel(c.day)"></span>
                                                <span class="text-[10px] font-bold" x-text="cellLabel(c.day)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> Present</span>
                                <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-amber-500"></span> Half</span>
                                <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-red-500"></span> Absent</span>
                                <span>Locked = pehle se lagi · faded = future</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 p-4">
                            <button type="submit" :disabled="selCount===0"
                                class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">
                                Mark din — <span x-text="statusName"></span> <span x-show="selCount>0">(<span x-text="selCount"></span>)</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ================= ADJUSTMENT ================= --}}
        <div x-show="tab==='adjustment'" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-card title="Adjustment (کٹوتی / بونس)">
                    <p class="mb-3 text-xs text-gray-500">Worker se paisa kaatna (nuksan/fine) ya bonus dena. Ye "Baqi/Advance" me net ho jata hai.</p>
                    <form method="POST" action="{{ route('projects.adjustment.store', $project) }}" class="space-y-3">
                        @csrf
                        <select name="worker_id" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— worker chuno —</option>
                            @foreach($allWorkers as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                        </select>
                        <select name="type" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="deduction">Katauti / Deduction (paisa kaato)</option>
                            <option value="bonus">Bonus / Extra (paisa barhao)</option>
                        </select>
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <x-money-input name="amount" label="Amount (رقم)" required />
                        <input type="text" name="notes" placeholder="Wajah (nuksan, fine, eid bonus…)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <button class="w-full rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Save Adjustment (محفوظ)</button>
                    </form>
                </x-card>
            </div>
            <div class="lg:col-span-2">
                <x-card title="Adjustments (کٹوتی / بونس)" class="!p-0">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Worker</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Wajah</th><th class="px-4 py-3 text-right">Amount</th><th></th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($adjustments as $a)
                                @php $isDed = $a->type === 'advance_given'; @endphp
                                <tr>
                                    <td class="px-4 py-2.5">{{ $a->date->format('d-m-Y') }}</td>
                                    <td class="px-4 py-2.5 font-medium text-gray-700">{{ $a->worker->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5"><x-badge :color="$isDed ? 'red' : 'emerald'">{{ $isDed ? 'Katauti' : 'Bonus' }}</x-badge></td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $a->notes }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium {{ $isDed ? 'text-rose-600' : 'text-emerald-600' }}">{{ $isDed ? '−' : '+' }}@money($a->amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-right whitespace-nowrap"><a href="{{ route('vouchers.advance', $a) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800" title="Print voucher">🖨</a><form method="POST" action="{{ route('worker-advances.destroy', $a) }}" class="ml-1 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                                </tr>
                            @empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi adjustment nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= OTHER EXPENSES ================= --}}
        <div x-show="tab==='expenses'" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-card title="Kharch add karo (خرچ شامل کریں)">
                    <form method="POST" action="{{ route('projects.expenses.store', $project) }}" class="space-y-3">
                        @csrf
                        <select name="category" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($expenseCategories as $c)<option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>@endforeach
                        </select>
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <input type="text" name="description" placeholder="Tafseel (diesel, transport…)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <x-money-input name="amount" label="Amount (رقم)" required />
                        <input type="text" name="paid_to" placeholder="Kisko diya (optional)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add (شامل کریں)</button>
                    </form>
                </x-card>
            </div>
            <div class="lg:col-span-2">
                <x-card title="Other Expenses (دیگر اخراجات)" class="!p-0">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Tafseel</th><th class="px-4 py-3 text-right">Amount</th><th></th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($project->otherExpenses->sortByDesc('date') as $e)
                                <tr><td class="px-4 py-2.5">{{ $e->date->format('d-m-Y') }}</td><td class="px-4 py-2.5 capitalize">{{ str_replace('_',' ',$e->category) }}</td><td class="px-4 py-2.5 text-gray-500">{{ $e->description }}</td><td class="px-4 py-2.5 text-right font-medium">@money($e->amount_paisa)</td>
                                <td class="px-4 py-2.5 text-right whitespace-nowrap"><a href="{{ route('vouchers.expense', $e) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800" title="Print voucher">🖨</a><form method="POST" action="{{ route('expenses.destroy', $e) }}" class="ml-1 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td></tr>
                            @empty<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Koi kharch nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= LENA / DENA (Payable / Receivable) ================= --}}
        @php
            $workersDue = $projectWorkers->map(fn($r) => ['w'=>$r['worker'], 'net'=>$r['earned']-$r['paid']-($r['advnet']??0)])->filter(fn($r)=>$r['net']>0)->values();
            $workerDueTotal = $workersDue->sum('net');
            $workersAdvance = $projectWorkers->map(fn($r) => ['w'=>$r['worker'], 'adv'=>-($r['earned']-$r['paid']-($r['advnet']??0))])->filter(fn($r)=>$r['adv']>0)->values();
            $workerAdvanceTotal = $workersAdvance->sum('adv');
            $vendorDue = $project->materialPurchases->filter(fn($p)=>$p->balance_due_paisa>0)->sortByDesc('date');
            $vendorDueTotal = (int) $project->materialPurchases->sum('balance_due_paisa');
        @endphp
        <div x-show="tab==='ledger'" x-cloak x-data="{ open:'recv' }" class="space-y-4 max-w-4xl">

            {{-- RECEIVABLE: client se lena --}}
            <x-card class="!p-0">
                <button type="button" @click="open = open==='recv' ? '' : 'recv'" class="flex w-full items-center justify-between p-4 text-left">
                    <span class="font-semibold text-gray-900">📥 Lena hai — Client se (وصول کرنا)</span>
                    <span class="flex items-center gap-2"><span class="text-lg font-bold text-emerald-600">@money($balance)</span><span class="text-gray-400" x-text="open==='recv' ? '▲' : '▼'"></span></span>
                </button>
                <div x-show="open==='recv'" class="border-t border-gray-100 p-4">
                    @if($balance > 0)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-emerald-50 p-3">
                            <div><div class="font-medium text-gray-800">{{ $project->client_name ?? 'Client' }}</div><div class="text-xs text-gray-500">Balance lena: @money($balance)</div></div>
                            <form method="POST" action="{{ route('projects.payments.store', $project) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                <input type="hidden" name="payment_method" value="cash">
                                <input type="number" step="0.01" name="amount" value="{{ \App\Support\Money::toRupees($balance) }}" class="w-32 rounded border-gray-300 px-2 py-1.5 text-sm">
                                <button class="rounded-md bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700">Receive (وصول)</button>
                            </form>
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Client se kuch baqi nahi — poora vasool ho gaya.</p>
                    @endif
                </div>
            </x-card>

            {{-- RECEIVABLE: worker advances (mazdoor se wapas lena) --}}
            <x-card class="!p-0">
                <button type="button" @click="open = open==='wadv' ? '' : 'wadv'" class="flex w-full items-center justify-between p-4 text-left">
                    <span class="font-semibold text-gray-900">📥 Lena hai — Mazdoor se advance wapas (پیشگی)</span>
                    <span class="flex items-center gap-2"><span class="text-lg font-bold text-amber-600">@money($workerAdvanceTotal)</span><span class="text-gray-400" x-text="open==='wadv' ? '▲' : '▼'"></span></span>
                </button>
                <div x-show="open==='wadv'" class="border-t border-gray-100 p-4 space-y-2">
                    @forelse($workersAdvance as $row)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-amber-50 p-3">
                            <div><div class="font-medium text-gray-800">{{ $row['w']->name }}</div><div class="text-xs text-gray-500">Advance diya hua — wapas lena: @money($row['adv'])</div></div>
                            <span class="text-xs text-gray-400">Agle kaam ki mazdoori se ya cash wapas — Adjustment me adjust hota.</span>
                        </div>
                    @empty<p class="text-sm text-gray-400">Kisi mazdoor ko zyada advance nahi gaya.</p>@endforelse
                </div>
            </x-card>

            {{-- PAYABLE: workers --}}
            <x-card class="!p-0">
                <button type="button" @click="open = open==='wrk' ? '' : 'wrk'" class="flex w-full items-center justify-between p-4 text-left">
                    <span class="font-semibold text-gray-900">📤 Dena hai — Mazdoor (مزدور)</span>
                    <span class="flex items-center gap-2"><span class="text-lg font-bold text-red-600">@money($workerDueTotal)</span><span class="text-gray-400" x-text="open==='wrk' ? '▲' : '▼'"></span></span>
                </button>
                <div x-show="open==='wrk'" class="border-t border-gray-100 p-4 space-y-2">
                    @forelse($workersDue as $row)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-gray-50 p-3">
                            <div><div class="font-medium text-gray-800">{{ $row['w']->name }} <span class="text-xs font-normal text-gray-400">#{{ $row['w']->id }} · {{ $row['w']->role }}</span></div><div class="text-xs text-gray-500">Dena: @money($row['net'])</div></div>
                            <form method="POST" action="{{ route('projects.wage-payments.store', $project) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="worker_id" value="{{ $row['w']->id }}">
                                <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                                <input type="hidden" name="override" value="1">
                                <input type="number" step="0.01" name="amount" value="{{ \App\Support\Money::toRupees($row['net']) }}" class="w-32 rounded border-gray-300 px-2 py-1.5 text-sm">
                                <button class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">Pay (ادائیگی)</button>
                            </form>
                        </div>
                    @empty<p class="text-sm text-gray-400">Kisi mazdoor ka kuch baqi nahi.</p>@endforelse
                </div>
            </x-card>

            {{-- PAYABLE: vendors/suppliers --}}
            <x-card class="!p-0">
                <button type="button" @click="open = open==='vnd' ? '' : 'vnd'" class="flex w-full items-center justify-between p-4 text-left">
                    <span class="font-semibold text-gray-900">📤 Dena hai — Supplier / Sub-contractor (اُدھار)</span>
                    <span class="flex items-center gap-2"><span class="text-lg font-bold text-red-600">@money($vendorDueTotal)</span><span class="text-gray-400" x-text="open==='vnd' ? '▲' : '▼'"></span></span>
                </button>
                <div x-show="open==='vnd'" class="border-t border-gray-100 p-4 space-y-2">
                    @forelse($vendorDue as $pur)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-gray-50 p-3">
                            <div><div class="font-medium text-gray-800">{{ $pur->vendor->name ?? 'Vendor' }} <span class="text-xs text-gray-400">· {{ $pur->item_name }}</span></div><div class="text-xs text-gray-500">Udhaar: @money($pur->balance_due_paisa)</div></div>
                            <form method="POST" action="{{ route('materials.pay', $pur) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="number" step="0.01" name="amount" value="{{ \App\Support\Money::toRupees($pur->balance_due_paisa) }}" class="w-32 rounded border-gray-300 px-2 py-1.5 text-sm">
                                <button class="rounded-md bg-amber-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-amber-700">Pay (ادائیگی)</button>
                            </form>
                        </div>
                    @empty<p class="text-sm text-gray-400">Kisi supplier ka udhaar baqi nahi.</p>@endforelse
                </div>
            </x-card>
        </div>

        {{-- ================= ESTIMATE ================= --}}
        <div x-show="tab==='estimates'" x-cloak>
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm text-gray-500">Budget total: <span class="font-bold text-gray-900">@money((int)$project->estimates->sum('amount_paisa'))</span></span>
                <a href="{{ route('estimates.index', $project) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Manage Estimate (تخمینہ بنائیں)</a>
            </div>
            <x-card class="!p-0">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Cat</th><th class="px-4 py-3 text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($project->estimates as $e)
                            <tr><td class="px-4 py-2.5 font-medium text-gray-700">{{ $e->item_name }}</td><td class="px-4 py-2.5 capitalize text-gray-500">{{ $e->category }}</td><td class="px-4 py-2.5 text-right font-medium">@money($e->amount_paisa)</td></tr>
                        @empty<tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Koi estimate nahi. "Manage Estimate" se banao.</td></tr>@endforelse
                    </tbody>
                </table>
            </x-card>
        </div>
    </div>

    @push('head')
    <script>
        // Per-worker attendance calendar (Present / Half / Absent). Loaded before Alpine starts.
        window.attendanceHub = function (cfg) {
            return {
                marked: cfg.marked || {},
                today: cfg.today,
                curYear: cfg.curYear,
                curMon: cfg.curMon,
                open: false,
                wId: null,
                wName: '',
                year: cfg.curYear,
                month: cfg.curMon,
                status: 'present',
                sel: {},

                openModal(id, name) {
                    this.wId = id; this.wName = name; this.sel = {};
                    this.status = 'present'; this.year = this.curYear; this.month = this.curMon;
                    this.open = true;
                },
                close() { this.open = false; },

                pad(n) { return n < 10 ? '0' + n : '' + n; },
                mInt() { return parseInt(this.month, 10); },
                dateStr(day) { return this.year + '-' + this.month + '-' + this.pad(day); },
                daysInMonth() { return new Date(this.year, this.mInt(), 0).getDate(); },
                firstWeekday() { return new Date(this.year, this.mInt() - 1, 1).getDay(); },
                weekdayLabel(day) { return ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][new Date(this.year, this.mInt() - 1, day).getDay()]; },

                get cells() {
                    const out = [];
                    const fw = this.firstWeekday();
                    for (let i = 0; i < fw; i++) out.push({ blank: true });
                    const n = this.daysInMonth();
                    for (let d = 1; d <= n; d++) out.push({ blank: false, day: d });
                    return out;
                },

                markedVal(day) {
                    const w = this.marked[this.wId];
                    if (!w) return null;
                    const v = w[this.dateStr(day)];
                    return (v === undefined) ? null : v;
                },
                isLocked(day) { return this.markedVal(day) !== null; },
                isFuture(day) { return this.dateStr(day) > this.today; },
                isSelected(day) { return !!this.sel[this.dateStr(day)]; },

                toggle(day) {
                    if (this.isLocked(day) || this.isFuture(day)) return;
                    const d = this.dateStr(day);
                    if (this.sel[d]) delete this.sel[d]; else this.sel[d] = true;
                },

                statusShort(s) { return s === 'present' ? 'P' : (s === 'half' ? 'H' : 'A'); },
                get statusName() { return this.status === 'present' ? 'Present' : (this.status === 'half' ? 'Half' : 'Absent'); },

                cellLabel(day) {
                    const v = this.markedVal(day);
                    if (v !== null) return v === 1 ? 'P' : (v === 0.5 ? 'H' : 'A');
                    if (this.isSelected(day)) return this.statusShort(this.status);
                    return '';
                },
                cellClass(day) {
                    const v = this.markedVal(day);
                    if (v !== null) {
                        const c = v === 1 ? 'bg-emerald-500 border-emerald-500' : (v === 0.5 ? 'bg-amber-500 border-amber-500' : 'bg-red-500 border-red-500');
                        return c + ' text-white cursor-not-allowed';
                    }
                    if (this.isFuture(day)) return 'bg-gray-50 border-gray-100 text-gray-300 cursor-not-allowed';
                    if (this.isSelected(day)) return 'bg-indigo-600 border-indigo-600 text-white';
                    return 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50';
                },

                get selCount() { return Object.keys(this.sel).length; },
                get selDates() { return JSON.stringify(Object.keys(this.sel)); },
            };
        };
    </script>
    @endpush
</x-app-layout>
