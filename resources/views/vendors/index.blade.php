<x-app-layout>
    <x-slot name="title">Vendors</x-slot>
    <x-slot name="header">Vendors (سپلائرز)</x-slot>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-stat label="Total Payable — udhaar (کل اُدھار)" :value="\App\Support\Money::format($totalPayable)" color="text-red-600" />
        <a href="{{ route('vendors.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ New Vendor (نیا سپلائر)</a>
    </div>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name (نام)</th>
                        <th class="px-4 py-3">Type (قسم)</th>
                        <th class="px-4 py-3">Phone (فون)</th>
                        <th class="px-4 py-3 text-right">Payable (اُدھار)</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vendors as $v)
                        @php $payable = (int)$v->opening_balance_paisa + (int)($v->balance_sum ?? 0); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5"><a href="{{ route('vendors.show', $v) }}" class="font-medium text-emerald-700 hover:underline">{{ $v->name }}</a></td>
                            <td class="px-4 py-2.5"><x-badge :color="$v->type==='subcontractor'?'indigo':'gray'">{{ ucfirst($v->type) }}</x-badge></td>
                            <td class="px-4 py-2.5 text-gray-500">{{ $v->phone }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $payable > 0 ? 'text-red-600' : 'text-gray-400' }}">@money($payable)</td>
                            <td class="px-4 py-2.5 text-right"><a href="{{ route('vendors.edit', $v) }}" class="text-gray-400 hover:text-gray-700">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Koi vendor nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vendors->hasPages())<div class="border-t border-gray-100 p-3">{{ $vendors->links() }}</div>@endif
    </x-card>
</x-app-layout>
