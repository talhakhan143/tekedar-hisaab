<x-app-layout>
    <x-slot name="title">{{ $worker->name }}</x-slot>
    <x-slot name="header">{{ $worker->name }}</x-slot>

    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('workers.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Workers</a>
        <x-badge color="gray">{{ ucfirst($worker->role) }}</x-badge>
        <span class="text-sm text-gray-500">@money($worker->default_wage_paisa) / {{ str_replace('contract_piece','piece',$worker->wage_type) }} · {{ $worker->phone }}</span>
        <span class="flex-1"></span>
        <a href="{{ route('workers.edit', $worker) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
    </div>

    {{-- Ledger summary --}}
    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-stat label="Earned — wages (کمایا)" :value="\App\Support\Money::format($earned)" />
        <x-stat label="Paid (دیا)" :value="\App\Support\Money::format($paid)" color="text-emerald-600" />
        <x-stat label="Advances Outstanding (پیشگی باقی)" :value="\App\Support\Money::format($advances)" color="text-amber-600" />
        <x-stat label="Payable Now (قابلِ ادائیگی)" :value="\App\Support\Money::format($payable)" :color="$payable < 0 ? 'text-red-600' : 'text-indigo-600'" sub="Kamaya − diya − peshgi" />
    </div>

    {{-- Entry forms --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Attendance / work --}}
        <x-card title="Add Attendance / Work (حاضری لگائیں)">
            <form method="POST" action="{{ route('work-entries.store', $worker) }}" class="space-y-3">
                @csrf
                <select name="project_id" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— no project —</option>
                    @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
                <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @if($worker->wage_type === 'contract_piece')
                    <input type="number" step="0.001" name="units_done" placeholder="Units done" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @else
                    <input type="number" step="0.5" name="days_present" placeholder="Days (0.5 = half)" value="1" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @endif
                <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add Entry</button>
            </form>
        </x-card>

        {{-- Advance (peshgi) --}}
        <x-card title="Advance / Recovery — Peshgi (پیشگی / وصولی)">
            <form method="POST" action="{{ route('worker-advances.store', $worker) }}" class="space-y-3">
                @csrf
                <select name="type" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="advance_given">Advance Given</option>
                    <option value="recovery">Recovery</option>
                </select>
                <select name="project_id" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— no project —</option>
                    @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
                <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <x-money-input name="amount" label="Amount" required />
                <button class="w-full rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">Record</button>
            </form>
        </x-card>

        {{-- Wage payment --}}
        <x-card title="Pay Wages (مزدوری دیں)">
            <form method="POST" action="{{ route('wage-payments.store', $worker) }}" class="space-y-3" x-data="{ override: false }">
                @csrf
                <div class="rounded-md bg-indigo-50 px-3 py-2 text-center text-sm">
                    Payable now: <span class="font-bold text-indigo-700">@money($payable)</span>
                </div>
                <select name="project_id" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">— no project —</option>
                    @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
                <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <input type="text" name="period_label" placeholder="Period (e.g. Jun wk2)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <x-money-input name="amount" label="Amount" required />
                <label class="flex items-center gap-2 text-xs text-gray-600">
                    <input type="hidden" name="override" value="0">
                    <input type="checkbox" name="override" value="1" x-model="override" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    Override (pay more than payable)
                </label>
                <x-input-error :messages="$errors->get('amount')" />
                <button class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Pay</button>
            </form>
        </x-card>
    </div>

    {{-- Ledgers --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Attendance / Work" class="!p-0">
            <div class="max-h-80 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="sticky top-0 bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Date</th><th class="px-3 py-2">Days/Units</th><th class="px-3 py-2 text-right">Wage</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($worker->workEntries as $e)
                            <tr>
                                <td class="px-3 py-2">{{ $e->date->format('d-m-y') }}</td>
                                <td class="px-3 py-2">{{ $e->units_done ? rtrim(rtrim($e->units_done,'0'),'.').' u' : rtrim(rtrim($e->days_present,'0'),'.').' d' }}</td>
                                <td class="px-3 py-2 text-right">@money($e->computed_wage_paisa)</td>
                                <td class="px-3 py-2 text-right"><form method="POST" action="{{ route('work-entries.destroy', $e) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                            </tr>
                        @empty<tr><td colspan="4" class="px-3 py-4 text-center text-gray-400">—</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="Advances (Peshgi)" class="!p-0">
            <div class="max-h-80 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="sticky top-0 bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Date</th><th class="px-3 py-2">Type</th><th class="px-3 py-2 text-right">Amount</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($worker->advances as $a)
                            <tr>
                                <td class="px-3 py-2">{{ $a->date->format('d-m-y') }}</td>
                                <td class="px-3 py-2"><x-badge :color="$a->type==='advance_given'?'amber':'emerald'">{{ $a->type==='advance_given'?'Given':'Recovery' }}</x-badge></td>
                                <td class="px-3 py-2 text-right">@money($a->amount_paisa)</td>
                                <td class="px-3 py-2 text-right"><form method="POST" action="{{ route('worker-advances.destroy', $a) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                            </tr>
                        @empty<tr><td colspan="4" class="px-3 py-4 text-center text-gray-400">—</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="Wage Payments" class="!p-0">
            <div class="max-h-80 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="sticky top-0 bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Date</th><th class="px-3 py-2">Period</th><th class="px-3 py-2 text-right">Amount</th><th></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($worker->wagePayments as $wp)
                            <tr>
                                <td class="px-3 py-2">{{ $wp->date->format('d-m-y') }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $wp->period_label }}</td>
                                <td class="px-3 py-2 text-right text-emerald-600">@money($wp->amount_paisa)</td>
                                <td class="px-3 py-2 text-right"><form method="POST" action="{{ route('wage-payments.destroy', $wp) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                            </tr>
                        @empty<tr><td colspan="4" class="px-3 py-4 text-center text-gray-400">—</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
