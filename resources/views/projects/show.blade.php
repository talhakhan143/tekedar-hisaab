<x-app-layout>
    <x-slot name="title">{{ $project->name }}</x-slot>
    <x-slot name="header">{{ $project->name }}</x-slot>

    @php $perSqft = $project->pricing_mode === 'per_sqft'; @endphp

    {{-- Action bar --}}
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <x-badge :color="$project->statusColor()">{{ ucfirst(str_replace('_',' ',$project->status)) }}</x-badge>
        <span class="text-sm text-gray-500">{{ $project->client_name }} · {{ $project->client_phone }}</span>
        <span class="flex-1"></span>
        @if(Route::has('client-payments.create'))
            <a href="{{ route('client-payments.create', ['project' => $project->id]) }}" class="rounded-md bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">+ Payment</a>
        @endif
        @if(Route::has('material-purchases.create'))
            <a href="{{ route('material-purchases.create', ['project' => $project->id]) }}" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">+ Purchase</a>
        @endif
        @if(Route::has('estimates.index'))
            <a href="{{ route('estimates.index', $project) }}" class="rounded-md bg-gray-700 px-3 py-2 text-sm font-medium text-white hover:bg-gray-600">Estimates</a>
        @endif
        <a href="{{ route('projects.edit', $project) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
    </div>

    {{-- Profit headline --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Accrued Profit" :value="\App\Support\Money::format($f->accruedProfitPaisa())"
                :color="$f->accruedProfitPaisa() < 0 ? 'text-red-600' : 'text-emerald-600'"
                sub="Gross billed − cost so far" />
        <x-stat label="Cash-in-hand Profit" :value="\App\Support\Money::format($f->cashProfitPaisa())"
                :color="$f->cashProfitPaisa() < 0 ? 'text-red-600' : 'text-indigo-600'"
                sub="Received+released − cash paid" />
        <x-stat label="Projected Final Profit" :value="\App\Support\Money::format($f->projectedProfitPaisa())"
                :color="$f->projectedProfitPaisa() < 0 ? 'text-red-600' : 'text-emerald-600'"
                sub="Contract value − cost so far" />
        @if($perSqft)
            <x-stat label="Margin / sq.ft" :value="\App\Support\Money::format($f->marginPerSqftPaisa())"
                    :color="($f->marginPerSqftPaisa() ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600'"
                    :sub="'Rate '.\App\Support\Money::format($project->rate_per_sqft_paisa,true,false).' − cost '.\App\Support\Money::format($f->actualCostPerSqftPaisa(),true,false)" />
        @else
            <x-stat label="Total Cost so far" :value="\App\Support\Money::format($f->totalAccruedCostPaisa())" color="text-amber-600" />
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Contract summary --}}
        <x-card title="Contract Summary">
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Type</dt><dd class="font-medium">{{ $project->contractTypeLabel() }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Pricing</dt><dd class="font-medium">{{ $perSqft ? 'Per sq.ft' : 'Lump sum' }}</dd></div>
                @if($perSqft)
                    <div class="flex justify-between"><dt class="text-gray-500">Covered Area</dt><dd class="font-medium">{{ number_format($project->covered_area_sqft) }} sq.ft</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Rate / sq.ft</dt><dd class="font-medium">@money($project->rate_per_sqft_paisa)</dd></div>
                @endif
                <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">Contract Value</dt><dd class="font-bold">@money($project->contract_value_paisa)</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Retention</dt><dd class="font-medium">{{ rtrim(rtrim($project->retention_percent,'0'),'.') }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Completion</dt><dd class="font-medium">{{ rtrim(rtrim($project->completion_percent,'0'),'.') }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Start</dt><dd class="font-medium">{{ optional($project->start_date)->format('d-m-Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Expected End</dt><dd class="font-medium">{{ optional($project->expected_end_date)->format('d-m-Y') ?? '—' }}</dd></div>
            </dl>
        </x-card>

        {{-- Money position --}}
        <x-card title="Money Position">
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Gross Billed</dt><dd class="font-medium">@money($f->grossBilledPaisa())</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Net Received</dt><dd class="font-medium text-emerald-600">@money($f->netReceivedPaisa())</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Retention Held</dt><dd class="font-medium text-amber-600">@money($f->retentionHeldPaisa())</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Retention Released</dt><dd class="font-medium">@money($f->retentionReleasedPaisa())</dd></div>
                <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">Retention Outstanding</dt><dd class="font-bold text-amber-600">@money($f->retentionOutstandingPaisa())</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Balance Receivable</dt><dd class="font-bold">@money($f->receivablePaisa())</dd></div>
            </dl>
        </x-card>

        {{-- Cost ledger --}}
        <x-card title="Cost Ledger (actual)">
            <dl class="space-y-2.5 text-sm">
                @foreach($f->actualByCategory() as $cat => $amt)
                    @if($amt > 0)
                        <div class="flex justify-between"><dt class="text-gray-500 capitalize">{{ str_replace('_',' ',$cat) }}</dt><dd class="font-medium">@money($amt)</dd></div>
                    @endif
                @endforeach
                <div class="flex justify-between border-t pt-2"><dt class="font-medium text-gray-700">Total Cost</dt><dd class="font-bold text-amber-600">@money($f->totalAccruedCostPaisa())</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Cash Paid Out</dt><dd class="font-medium">@money($f->totalCashPaidPaisa())</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Vendor Payable (udhaar)</dt><dd class="font-medium text-red-600">@money($f->vendorPayablePaisa())</dd></div>
            </dl>
        </x-card>
    </div>

    {{-- Estimate vs Actual --}}
    <div class="mt-6">
        <x-card title="Estimate vs Actual (variance — overruns in red)" class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3 text-right">Estimated</th>
                            <th class="px-4 py-3 text-right">Actual</th>
                            <th class="px-4 py-3 text-right">Variance</th>
                            <th class="px-4 py-3 text-right">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($variance as $v)
                            @if($v['estimate'] > 0 || $v['actual'] > 0)
                                <tr class="{{ $v['over'] ? 'bg-red-50' : '' }}">
                                    <td class="px-4 py-2.5 capitalize font-medium text-gray-700">{{ $v['category'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-600">@money($v['estimate'])</td>
                                    <td class="px-4 py-2.5 text-right text-gray-600">@money($v['actual'])</td>
                                    <td class="px-4 py-2.5 text-right font-semibold {{ $v['variance'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                        {{ $v['variance'] > 0 ? '+' : '' }}@money($v['variance'])
                                    </td>
                                    <td class="px-4 py-2.5 text-right {{ $v['over'] ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                        {{ $v['pct'] === null ? '—' : ($v['pct'] > 0 ? '+' : '').$v['pct'].'%' }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 font-semibold">
                        <tr>
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right">@money(collect($variance)->sum('estimate'))</td>
                            <td class="px-4 py-3 text-right">@money(collect($variance)->sum('actual'))</td>
                            <td class="px-4 py-3 text-right {{ collect($variance)->sum('variance') > 0 ? 'text-red-600' : 'text-emerald-600' }}">@money(collect($variance)->sum('variance'))</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Client payments ledger --}}
    <div class="mt-6">
        <x-card title="Client Payments Ledger" class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Ref</th>
                            <th class="px-4 py-3 text-right">Gross</th>
                            <th class="px-4 py-3 text-right">Retention</th>
                            <th class="px-4 py-3 text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($project->clientPayments as $pay)
                            <tr>
                                <td class="px-4 py-2.5">{{ $pay->date->format('d-m-Y') }}</td>
                                <td class="px-4 py-2.5 capitalize">{{ $pay->payment_method }} @if($pay->is_mobilization)<x-badge color="sky">Mobilization</x-badge>@endif</td>
                                <td class="px-4 py-2.5 text-gray-500">{{ $pay->reference }}</td>
                                <td class="px-4 py-2.5 text-right">@money($pay->gross_amount_paisa)</td>
                                <td class="px-4 py-2.5 text-right text-amber-600">@money($pay->retention_held_paisa)</td>
                                <td class="px-4 py-2.5 text-right font-medium text-emerald-600">@money($pay->net_received_paisa)</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi payment nahi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
