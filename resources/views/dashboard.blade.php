<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">Dashboard</x-slot>

    @push('head')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @endpush

    {{-- Headline cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat label="Active Projects (چالو پروجیکٹس)" :value="number_format($activeProjects)" color="text-emerald-600" />
        <x-stat label="Total Contract Value (کل ٹھیکہ مالیت)" :value="\App\Support\Money::short($contractValue)" color="text-sky-600" />
        <x-stat label="This Month — Net Profit (اس ماہ کا منافع)" :value="\App\Support\Money::short($monthNetProfit)"
                :color="$monthNetProfit < 0 ? 'text-red-600' : 'text-indigo-600'"
                :sub="'In '.\App\Support\Money::short($monthReceived).' · Out '.\App\Support\Money::short($monthSpent)" />
    </div>

    {{-- Lena / Dena overview --}}
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl bg-emerald-50 p-5 ring-1 ring-emerald-200">
            <div class="text-sm font-medium text-emerald-700">📥 Total Lena — Receivable (کل وصولی)</div>
            <div class="mt-2 text-3xl font-bold text-emerald-700">{{ \App\Support\Money::short($totalReceivable) }}</div>
            <div class="mt-1 text-xs text-emerald-600/70">Client se baqi (saare projects)</div>
        </div>
        <div class="rounded-xl bg-rose-50 p-5 ring-1 ring-rose-200">
            <div class="text-sm font-medium text-rose-700">📤 Total Dena — Payable (کل واجبات)</div>
            <div class="mt-2 text-3xl font-bold text-rose-700">{{ \App\Support\Money::short($totalPayable) }}</div>
            <div class="mt-1 text-xs text-rose-600/70">Vendor udhaar + mazdoor baqi</div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-card title="Profit Trend — last 12 months (منافع کا رجحان)" class="lg:col-span-2">
            <div class="h-64"><canvas id="trendChart"></canvas></div>
        </x-card>
        <x-card title="Cost Breakdown (لاگت کی تقسیم)">
            <div class="h-64"><canvas id="costChart"></canvas></div>
        </x-card>
    </div>

    {{-- Overall profit + outstanding --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card title="Overall Profit — all-time (مجموعی منافع)">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Total Billed, incl. retention (کل بل)</dt><dd class="font-semibold">@money($totalBilled)</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Total Cost, incl. overheads (کل لاگت)</dt><dd class="font-semibold">@money($totalCost)</dd></div>
                <div class="flex justify-between border-t pt-3"><dt class="font-medium text-gray-700">Accrued Profit (کھاتہ منافع)</dt><dd class="font-bold {{ $accruedProfit < 0 ? 'text-red-600' : 'text-emerald-600' }}">@money($accruedProfit)</dd></div>
                <div class="flex justify-between"><dt class="font-medium text-gray-700">Cash-in-hand Profit (نقد منافع)</dt><dd class="font-bold {{ $cashProfit < 0 ? 'text-red-600' : 'text-indigo-600' }}">@money($cashProfit)</dd></div>
                <div class="mt-2 space-y-1 rounded-lg bg-gray-50 p-3 text-xs text-gray-500">
                    <p><span class="font-semibold text-emerald-700">Accrued (کھاتہ)</span> = kaagaz pe munafa (bill − lagat), chahe paisa abhi aaya ho ya nahi.</p>
                    <p><span class="font-semibold text-indigo-700">Cash (نقد)</span> = jeb wala munafa — jo paisa asal me aaya minus jo asal me diya. Roki gayi retention (₨{{ number_format($retentionOutstanding/100) }}) isme shaamil nahi.</p>
                </div>
            </dl>
        </x-card>
        <x-card title="Outstanding (واجبات)">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Vendor Payables — udhaar (سپلائر کا اُدھار)</dt><dd class="font-semibold text-red-600">@money($vendorPayables)</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Worker Advances (مزدور پیشگی)</dt><dd class="font-semibold text-amber-600">@money($workerAdvances)</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Retention Held by Client (کلائنٹ کے پاس روکی رقم)</dt><dd class="font-semibold text-amber-600">@money($retentionOutstanding)</dd></div>
            </dl>
        </x-card>
    </div>

    {{-- Top projects + loss flags --}}
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card title="Top Projects by Profit (سب سے زیادہ منافع)" class="!p-0">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse($topProjects as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5"><a href="{{ route('projects.show', $row['project']) }}" class="font-medium text-emerald-700 hover:underline">{{ $row['project']->name }}</a></td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $row['projected'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">@money($row['projected'])</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-gray-400">Koi project nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
        <x-card title="⚠️ Loss-making Projects (نقصان والے پروجیکٹس)" class="!p-0">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse($lossProjects as $row)
                        <tr class="bg-red-50">
                            <td class="px-4 py-2.5"><a href="{{ route('projects.show', $row['project']) }}" class="font-medium text-red-700 hover:underline">{{ $row['project']->name }}</a></td>
                            <td class="px-4 py-2.5 text-right font-semibold text-red-600">@money($row['projected'])</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-8 text-center text-emerald-600">✓ Koi loss-making project nahi. Sab profit me hain.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    @push('head')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const pkr = (v) => '₨ ' + Number(v).toLocaleString('en-PK');
                const trend = @json($profitTrend);
                const cost  = @json($costPie);

                new Chart(document.getElementById('trendChart'), {
                    type: 'line',
                    data: {
                        labels: trend.labels,
                        datasets: [{
                            label: 'Net Profit', data: trend.data,
                            borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.1)',
                            fill: true, tension: 0.3, pointRadius: 3,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => pkr(c.parsed.y) } } },
                        scales: { y: { ticks: { callback: v => '₨' + (v/1000).toFixed(0) + 'k' } } }
                    }
                });

                new Chart(document.getElementById('costChart'), {
                    type: 'doughnut',
                    data: {
                        labels: cost.labels,
                        datasets: [{ data: cost.data, backgroundColor: ['#f59e0b', '#6366f1', '#0ea5e9'] }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => c.label + ': ' + pkr(c.parsed) } } }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
