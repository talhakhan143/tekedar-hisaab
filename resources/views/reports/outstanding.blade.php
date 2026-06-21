<x-app-layout>
    <x-slot name="title">Outstanding Report</x-slot>
    <x-slot name="header">Outstanding Report</x-slot>

    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('reports') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Reports</a>
        <span class="flex-1"></span>
        <a href="{{ route('reports.outstanding', ['export'=>'csv']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Export CSV</a>
        <a href="{{ route('reports.outstanding', ['export'=>'pdf']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Export PDF</a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Receivables (from clients)" class="!p-0">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Project</th><th class="px-4 py-3 text-right">Retention</th><th class="px-4 py-3 text-right">Receivable</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($receivables as $r)<tr><td class="px-4 py-2.5">{{ $r['name'] }}</td><td class="px-4 py-2.5 text-right text-amber-600">@money($r['retention'])</td><td class="px-4 py-2.5 text-right font-medium">@money($r['receivable'])</td></tr>
                    @empty<tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">—</td></tr>@endforelse
                </tbody>
            </table>
        </x-card>
        <x-card title="Payables (to vendors)" class="!p-0">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Vendor</th><th class="px-4 py-3 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payables as $r)<tr><td class="px-4 py-2.5">{{ $r['name'] }}</td><td class="px-4 py-2.5 text-right font-medium text-red-600">@money($r['amount'])</td></tr>
                    @empty<tr><td colspan="2" class="px-4 py-6 text-center text-gray-400">—</td></tr>@endforelse
                </tbody>
            </table>
        </x-card>
        <x-card title="Worker Advances" class="!p-0">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Worker</th><th class="px-4 py-3 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($advances as $r)<tr><td class="px-4 py-2.5">{{ $r['name'] }}</td><td class="px-4 py-2.5 text-right font-medium text-amber-600">@money($r['amount'])</td></tr>
                    @empty<tr><td colspan="2" class="px-4 py-6 text-center text-gray-400">—</td></tr>@endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</x-app-layout>
