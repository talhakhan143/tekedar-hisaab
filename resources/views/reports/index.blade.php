<x-app-layout>
    <x-slot name="title">Reports</x-slot>
    <x-slot name="header">Reports (رپورٹس)</x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Monthly Profit Report (ماہانہ منافع)">
            <form method="GET" action="{{ route('reports.monthly') }}" class="space-y-3">
                <input type="month" name="month" value="{{ $thisMonth }}" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <div class="flex gap-2">
                    <button class="flex-1 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">View</button>
                    <button name="export" value="csv" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">CSV</button>
                    <button name="export" value="pdf" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">PDF</button>
                </div>
                <p class="text-xs text-gray-400">All projects + overheads for the month.</p>
            </form>
        </x-card>

        <x-card title="Outstanding Report (واجبات رپورٹ)">
            <div class="space-y-3">
                <p class="text-sm text-gray-500">Client receivables + vendor payables + worker advances.</p>
                <div class="flex gap-2">
                    <a href="{{ route('reports.outstanding') }}" class="flex-1 rounded-md bg-emerald-600 px-3 py-2 text-center text-sm font-semibold text-white hover:bg-emerald-700">View</a>
                    <a href="{{ route('reports.outstanding', ['export'=>'csv']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">CSV</a>
                    <a href="{{ route('reports.outstanding', ['export'=>'pdf']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">PDF</a>
                </div>
            </div>
        </x-card>

        <x-card title="Project Closeout Report (پروجیکٹ کلوزآؤٹ)">
            <form method="GET" action="{{ url('reports/closeout') }}/{{ $projects->first()?->id }}" class="space-y-3" x-data="{ pid: '{{ $projects->first()?->id }}' }" @submit="$el.action = '{{ url('reports/closeout') }}/' + pid">
                <select x-model="pid" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
                <div class="flex gap-2">
                    <button class="flex-1 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">View</button>
                    <button name="export" value="pdf" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">PDF</button>
                </div>
                <p class="text-xs text-gray-400">Final P&L for a project.</p>
            </form>
        </x-card>
    </div>
</x-app-layout>
