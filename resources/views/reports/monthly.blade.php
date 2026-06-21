<x-app-layout>
    <x-slot name="title">Monthly Profit · {{ $carbon->format('M Y') }}</x-slot>
    <x-slot name="header">Monthly Profit Report · {{ $carbon->format('M Y') }}</x-slot>

    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('reports') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Reports</a>
        <span class="flex-1"></span>
        <a href="{{ route('reports.monthly', ['month'=>$month,'export'=>'csv']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Export CSV</a>
        <a href="{{ route('reports.monthly', ['month'=>$month,'export'=>'pdf']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Export PDF</a>
    </div>

    <div class="mb-5 grid grid-cols-3 gap-4">
        <x-stat label="Received" :value="\App\Support\Money::format($totalReceived)" color="text-emerald-600" />
        <x-stat label="Spent (+overheads)" :value="\App\Support\Money::format($totalSpent + $overheads)" color="text-amber-600" />
        <x-stat label="Net Profit" :value="\App\Support\Money::format($netProfit)" :color="$netProfit < 0 ? 'text-red-600' : 'text-indigo-600'" />
    </div>

    <x-card class="!p-0">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Project</th><th class="px-4 py-3 text-right">Received</th><th class="px-4 py-3 text-right">Spent</th><th class="px-4 py-3 text-right">Profit</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $r)
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-gray-700">{{ $r['name'] }}</td>
                        <td class="px-4 py-2.5 text-right text-emerald-600">@money($r['received'])</td>
                        <td class="px-4 py-2.5 text-right text-amber-600">@money($r['spent'])</td>
                        <td class="px-4 py-2.5 text-right font-semibold {{ $r['profit'] < 0 ? 'text-red-600' : '' }}">@money($r['profit'])</td>
                    </tr>
                @empty<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Is month koi activity nahi.</td></tr>@endforelse
                <tr class="bg-gray-50 font-semibold"><td class="px-4 py-3">General Overheads</td><td></td><td class="px-4 py-3 text-right text-amber-600">@money($overheads)</td><td></td></tr>
                <tr class="bg-gray-100 font-bold"><td class="px-4 py-3">NET PROFIT</td><td class="px-4 py-3 text-right">@money($totalReceived)</td><td class="px-4 py-3 text-right">@money($totalSpent + $overheads)</td><td class="px-4 py-3 text-right {{ $netProfit < 0 ? 'text-red-600' : 'text-indigo-600' }}">@money($netProfit)</td></tr>
            </tbody>
        </table>
    </x-card>
</x-app-layout>
