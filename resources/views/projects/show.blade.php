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
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
                <x-stat label="Contract (ٹھیکہ)" :value="\App\Support\Money::short($contract)" color="text-sky-600" />
                <x-stat label="Received (مل گیا)" :value="\App\Support\Money::short($received)" color="text-emerald-600" />
                <x-stat label="Balance (باقی)" :value="\App\Support\Money::short($balance)" color="text-amber-600" sub="client se lena" />
                <x-stat label="Expense (خرچ)" :value="\App\Support\Money::short($expense)" color="text-rose-600" />
                <x-stat label="Profit (منافع)" :value="\App\Support\Money::short($profit)" :color="$profit < 0 ? 'text-red-600' : 'text-indigo-600'" sub="contract − kharch" />
                <x-stat label="Completion" :value="rtrim(rtrim($project->completion_percent,'0'),'.').'%'" color="text-gray-700" />
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
                                    <td class="px-4 py-2.5 text-right"><form method="POST" action="{{ route('client-payments.destroy', $pay) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
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
                            <option value="">— vendor (optional) —</option>
                            @foreach($vendors as $vd)<option value="{{ $vd->id }}">{{ $vd->name }}</option>@endforeach
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
                            @forelse($project->materialPurchases->sortByDesc('date') as $pur)
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
                                        <form method="POST" action="{{ route('materials.destroy', $pur) }}" class="ml-1 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form>
                                    </td>
                                </tr>
                            @empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi material nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= ATTENDANCE / LABOUR ================= --}}
        <div x-show="tab==='attendance'" x-cloak>
            {{-- Day grid: project start -> current month. Marked days locked. --}}
            <div x-data="{
                    sel: {},
                    marked: {{ \Illuminate\Support\Js::from($attMarked) }},
                    isMarked(w,d){ return this.marked[w] && this.marked[w][d]; },
                    toggle(w,d){ if(this.isMarked(w,d)) return; let k=w+'|'+d; this.sel[k]=!this.sel[k]; },
                    on(w,d){ return !!this.sel[w+'|'+d]; },
                    selectRow(w, days){ days.forEach(d=>{ if(!this.isMarked(w,d)) this.sel[w+'|'+d]=true; }); },
                    get count(){ return Object.values(this.sel).filter(Boolean).length; },
                    payload(){ return JSON.stringify(Object.keys(this.sel).filter(k=>this.sel[k])); }
                 }" class="mb-6">
                <form method="POST" action="{{ route('projects.attendance.bulk', $project) }}">
                    @csrf
                    <input type="hidden" name="cells" :value="payload()">
                    <input type="hidden" name="att_month" value="{{ $attMonth }}">
                    <x-card class="!p-0">
                        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 p-3">
                            <h2 class="font-semibold text-gray-900">Attendance Grid (حاضری)</h2>
                            <div class="flex items-center gap-2" x-data="{ y:'{{ $attYear }}', m:'{{ $attMon }}', go(){ window.location='{{ route('projects.show', $project) }}?tab=attendance&att_month='+this.y+'-'+this.m } }">
                                <select x-model="y" @change="go()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach($attYears as $yr)<option value="{{ $yr }}" @selected((int)$yr===(int)$attYear)>{{ $yr }}</option>@endforeach
                                </select>
                                <select x-model="m" @change="go()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach($attMonthNames as $mv => $ml)<option value="{{ $mv }}" @selected($mv===$attMon)>{{ $ml }}</option>@endforeach
                                </select>
                            </div>
                            <span class="text-xs text-gray-400">Green ✓ = lagi (locked) · Blue = select · click karke lagao</span>
                            <span class="flex-1"></span>
                            <span class="text-sm text-gray-600"><span x-text="count"></span> din selected</span>
                            <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save Haazri (محفوظ)</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-[10px] text-gray-500">
                                        <th class="sticky left-0 z-10 bg-gray-50 px-3 py-2 text-left">Worker</th>
                                        @foreach($attDays as $d)
                                            <th class="w-9 px-0 py-1 text-center {{ $d['fri'] ? 'text-amber-600 font-semibold' : '' }}">
                                                <div class="opacity-60">{{ $d['wd'] }}</div><div class="text-xs font-semibold text-gray-700">{{ $d['d'] }}</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($attWorkers as $w)
                                        <tr>
                                            <td class="sticky left-0 z-10 bg-white px-3 py-1.5 font-medium text-gray-700 whitespace-nowrap">
                                                {{ $w->name }}
                                                <button type="button" @click="selectRow({{ $w->id }}, {{ \Illuminate\Support\Js::from(collect($attDays)->pluck('date')) }})" class="ml-1 text-[10px] text-emerald-600 hover:underline">all</button>
                                            </td>
                                            @foreach($attDays as $d)
                                                <td class="p-0.5 text-center">
                                                    <button type="button" @click="toggle({{ $w->id }}, '{{ $d['date'] }}')"
                                                        :disabled="isMarked({{ $w->id }}, '{{ $d['date'] }}')"
                                                        class="mx-auto flex h-7 w-7 items-center justify-center rounded text-xs font-bold"
                                                        :class="isMarked({{ $w->id }}, '{{ $d['date'] }}') ? 'bg-emerald-500 text-white cursor-not-allowed' : (on({{ $w->id }}, '{{ $d['date'] }}') ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-300 hover:bg-gray-200')"
                                                        x-text="isMarked({{ $w->id }}, '{{ $d['date'] }}') ? '✓' : (on({{ $w->id }}, '{{ $d['date'] }}') ? '✓' : '')"></button>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr><td class="px-4 py-6 text-center text-gray-400">Koi worker nahi. Niche se ya Attendance se add karo.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6">
                    <x-card title="Naya Mazdoor (نیا مزدور)">
                        <form method="POST" action="{{ route('attendance.quick-worker') }}" class="space-y-3">
                            @csrf
                            <input type="text" name="name" placeholder="Naam" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <select name="role" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach(['mistri','mazdoor','electrician','plumber','painter','foreman','other'] as $r)<option value="{{ $r }}">{{ ucfirst($r) }}</option>@endforeach
                            </select>
                            <x-money-input name="default_wage" label="Dihaadi (روزانہ)" required />
                            <button class="w-full rounded-md bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-700">Add Worker (شامل)</button>
                        </form>
                    </x-card>
                    <x-card title="Mazdoori do (ادائیگی)">
                        <form method="POST" action="{{ route('projects.wage-payments.store', $project) }}" class="space-y-3">
                            @csrf
                            <select name="worker_id" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">— worker chuno —</option>
                                @foreach($allWorkers as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                            </select>
                            <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <x-money-input name="amount" label="Amount (رقم)" required />
                            <label class="flex items-center gap-2 text-xs text-gray-600"><input type="hidden" name="override" value="0"><input type="checkbox" name="override" value="1" class="rounded border-gray-300 text-emerald-600"> Override (payable se zyada)</label>
                            <x-input-error :messages="$errors->get('amount')" />
                            <button class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Pay (ادائیگی)</button>
                        </form>
                    </x-card>
                </div>
                <div class="lg:col-span-2">
                    <x-card title="Is project ke mazdoor (لیبر)" class="!p-0">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Worker</th><th class="px-4 py-3 text-right">Days</th><th class="px-4 py-3 text-right">Earned</th><th class="px-4 py-3 text-right">Paid</th><th class="px-4 py-3 text-right">Baqi / Advance (باقی / پیشگی)</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($projectWorkers as $row)
                                    @php $net = $row['earned'] - $row['paid'] - ($row['advnet'] ?? 0); @endphp
                                    <tr><td class="px-4 py-2.5"><a href="{{ route('workers.show', $row['worker']) }}" class="font-medium text-emerald-700 hover:underline">{{ $row['worker']->name }}</a></td>
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
                                @php $totEarned=$projectWorkers->sum('earned'); $totPaid=$projectWorkers->sum('paid'); $totNet=$totEarned-$totPaid-$projectWorkers->sum('advnet'); @endphp
                                <tfoot class="bg-gray-50 font-semibold"><tr><td class="px-4 py-3">Total</td><td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format($projectWorkers->sum('days'),1),'0'),'.') }}</td><td class="px-4 py-3 text-right">@money($totEarned)</td><td class="px-4 py-3 text-right text-emerald-600">@money($totPaid)</td>
                                <td class="px-4 py-3 text-right">@if($totNet>=0)<span class="text-red-600">@money($totNet)</span> <span class="text-xs font-normal text-gray-400">dena</span>@else<span class="text-amber-600">@money(-$totNet)</span> <span class="text-xs font-normal text-gray-400">advance</span>@endif</td></tr></tfoot>
                            @endif
                        </table>
                    </x-card>
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
                                    <td class="px-4 py-2.5 text-right"><form method="POST" action="{{ route('worker-advances.destroy', $a) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
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
                                <td class="px-4 py-2.5 text-right"><form method="POST" action="{{ route('expenses.destroy', $e) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td></tr>
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

            {{-- PAYABLE: workers --}}
            <x-card class="!p-0">
                <button type="button" @click="open = open==='wrk' ? '' : 'wrk'" class="flex w-full items-center justify-between p-4 text-left">
                    <span class="font-semibold text-gray-900">📤 Dena hai — Mazdoor (مزدور)</span>
                    <span class="flex items-center gap-2"><span class="text-lg font-bold text-red-600">@money($workerDueTotal)</span><span class="text-gray-400" x-text="open==='wrk' ? '▲' : '▼'"></span></span>
                </button>
                <div x-show="open==='wrk'" class="border-t border-gray-100 p-4 space-y-2">
                    @forelse($workersDue as $row)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-gray-50 p-3">
                            <div><div class="font-medium text-gray-800">{{ $row['w']->name }}</div><div class="text-xs text-gray-500">Dena: @money($row['net'])</div></div>
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
                    <span class="font-semibold text-gray-900">📤 Dena hai — Supplier (سپلائر اُدھار)</span>
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
</x-app-layout>
