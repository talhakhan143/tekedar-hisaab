<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Headline cards (wired to real aggregates in Step 9) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                ['Active Projects',         $activeProjects ?? 0,        'count', 'text-emerald-600'],
                ['Total Contract Value',    $contractValue ?? 0,         'money', 'text-sky-600'],
                ['This Month — Net Profit', $monthNetProfit ?? 0,        'money', 'text-indigo-600'],
                ['Retention Outstanding',   $retentionOutstanding ?? 0,  'money', 'text-amber-600'],
            ];
        @endphp
        @foreach ($cards as [$label, $value, $type, $colorClass])
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
                <div class="mt-2 text-2xl font-bold {{ $colorClass }}">
                    {{ $type === 'money' ? \App\Support\Money::format($value, true, false) : number_format($value) }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Charts (wired in Step 9) --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
            <h2 class="font-semibold text-gray-900">Profit Trend (last 12 months)</h2>
            <div class="mt-4 flex h-64 items-center justify-center rounded-lg bg-gray-50 text-sm text-gray-400">
                Chart.js line chart — wired in Step 9
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h2 class="font-semibold text-gray-900">Cost Breakdown</h2>
            <div class="mt-4 flex h-64 items-center justify-center rounded-lg bg-gray-50 text-sm text-gray-400">
                Pie: material / labour / other
            </div>
        </div>
    </div>

    {{-- Accrued vs Cash + flags --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h2 class="font-semibold text-gray-900">Overall Profit</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Total Billed (incl. retention)</dt><dd class="font-semibold">{{ \App\Support\Money::format($totalBilled ?? 0) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Total Cost</dt><dd class="font-semibold">{{ \App\Support\Money::format($totalCost ?? 0) }}</dd></div>
                <div class="flex justify-between border-t pt-3"><dt class="font-medium text-gray-700">Accrued Profit</dt><dd class="font-bold text-emerald-600">{{ \App\Support\Money::format($accruedProfit ?? 0) }}</dd></div>
                <div class="flex justify-between"><dt class="font-medium text-gray-700">Cash-in-hand Profit</dt><dd class="font-bold text-indigo-600">{{ \App\Support\Money::format($cashProfit ?? 0) }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <h2 class="font-semibold text-gray-900">Outstanding</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Vendor Payables (udhaar)</dt><dd class="font-semibold text-amber-600">{{ \App\Support\Money::format($vendorPayables ?? 0) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Worker Advances Outstanding</dt><dd class="font-semibold text-amber-600">{{ \App\Support\Money::format($workerAdvances ?? 0) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Retention Held by Client</dt><dd class="font-semibold text-amber-600">{{ \App\Support\Money::format($retentionOutstanding ?? 0) }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-gray-400">Loss-making project flags appear here in Step 9.</p>
        </div>
    </div>
</x-app-layout>
