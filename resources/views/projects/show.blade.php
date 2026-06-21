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
                ['expenses',   'Other Expenses (اخراجات)'],
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
                                    <td class="px-4 py-2.5 text-right"><form method="POST" action="{{ route('materials.destroy', $pur) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                                </tr>
                            @empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi material nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>
        </div>

        {{-- ================= ATTENDANCE / LABOUR ================= --}}
        <div x-show="tab==='attendance'" x-cloak>
            <div class="mb-4 flex flex-wrap gap-2">
                <a href="{{ route('attendance') }}" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Quick Daily Mark (آج کی حاضری)</a>
                <a href="{{ route('attendance.register', ['project_id'=>$project->id]) }}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">📅 Month Register (مہینہ رجسٹر)</a>
            </div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6">
                    <x-card title="Haazri lagao (حاضری)">
                        <form method="POST" action="{{ route('projects.work-entries.store', $project) }}" class="space-y-3">
                            @csrf
                            <select name="worker_id" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">— worker chuno —</option>
                                @foreach($allWorkers as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                            </select>
                            <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <input type="number" step="0.5" name="days_present" value="1" placeholder="Din (0.5=half)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add (شامل)</button>
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
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Worker</th><th class="px-4 py-3 text-right">Days</th><th class="px-4 py-3 text-right">Earned</th><th class="px-4 py-3 text-right">Paid</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($projectWorkers as $row)
                                    <tr><td class="px-4 py-2.5"><a href="{{ route('workers.show', $row['worker']) }}" class="font-medium text-emerald-700 hover:underline">{{ $row['worker']->name }}</a></td>
                                    <td class="px-4 py-2.5 text-right">{{ rtrim(rtrim(number_format($row['days'],1),'0'),'.') }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium">@money($row['earned'])</td>
                                    <td class="px-4 py-2.5 text-right text-emerald-600">@money($row['paid'])</td></tr>
                                @empty<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Abhi koi haazri nahi.</td></tr>@endforelse
                            </tbody>
                            @if($projectWorkers->count())<tfoot class="bg-gray-50 font-semibold"><tr><td class="px-4 py-3">Total</td><td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format($projectWorkers->sum('days'),1),'0'),'.') }}</td><td class="px-4 py-3 text-right">@money($projectWorkers->sum('earned'))</td><td class="px-4 py-3 text-right text-emerald-600">@money($projectWorkers->sum('paid'))</td></tr></tfoot>@endif
                        </table>
                    </x-card>
                </div>
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
