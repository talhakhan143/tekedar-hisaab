<x-app-layout>
    <x-slot name="title">Workers</x-slot>
    <x-slot name="header">Workers (مزدور)</x-slot>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-stat label="Total Worker Advances (کل پیشگی)" :value="\App\Support\Money::format($totalAdvances)" color="text-amber-600" />
        <a href="{{ route('workers.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ New Worker (نیا مزدور)</a>
    </div>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name (نام)</th>
                        <th class="px-4 py-3">Role (کام)</th>
                        <th class="px-4 py-3">Wage (دیہاڑی)</th>
                        <th class="px-4 py-3 text-right">Earned (کمایا)</th>
                        <th class="px-4 py-3 text-right">Advances (پیشگی)</th>
                        <th class="px-4 py-3 text-right">Payable Now (قابلِ ادائیگی)</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php $w = $row['worker']; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5"><a href="{{ route('workers.show', $w) }}" class="font-medium text-emerald-700 hover:underline">{{ $w->name }}</a><div class="text-xs text-gray-400">{{ $w->phone }}</div></td>
                            <td class="px-4 py-2.5 capitalize">{{ $w->role }}</td>
                            <td class="px-4 py-2.5 text-gray-500">@money($w->default_wage_paisa) / {{ str_replace('contract_piece','piece',$w->wage_type) }}</td>
                            <td class="px-4 py-2.5 text-right">@money($row['earned'])</td>
                            <td class="px-4 py-2.5 text-right {{ $row['advances'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">@money($row['advances'])</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $row['payable'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">@money($row['payable'])</td>
                            <td class="px-4 py-2.5 text-right"><a href="{{ route('workers.edit', $w) }}" class="text-gray-400 hover:text-gray-700">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Koi worker nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>
