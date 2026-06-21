<x-app-layout>
    <x-slot name="title">{{ $vendor->name }}</x-slot>
    <x-slot name="header">{{ $vendor->name }}</x-slot>

    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('vendors.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Vendors</a>
        <x-badge :color="$vendor->type==='subcontractor'?'indigo':'gray'">{{ ucfirst($vendor->type) }}</x-badge>
        <span class="text-sm text-gray-500">{{ $vendor->phone }}</span>
        <span class="flex-1"></span>
        <a href="{{ route('vendors.edit', $vendor) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat label="Total Payable (udhaar)" :value="\App\Support\Money::format($payable)" :color="$payable > 0 ? 'text-red-600' : 'text-gray-400'" />
        <x-stat label="Opening Balance" :value="\App\Support\Money::format($vendor->opening_balance_paisa)" />
    </div>

    <x-card title="Ledger" class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ledger as $row)
                        <tr>
                            <td class="px-4 py-2.5">{{ $row['date'] ? $row['date']->format('d-m-Y') : '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ $row['desc'] }}</td>
                            <td class="px-4 py-2.5 text-right">@money($row['amount'])</td>
                            <td class="px-4 py-2.5 text-right text-emerald-600">@money($row['paid'])</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $row['running'] > 0 ? 'text-red-600' : 'text-gray-500' }}">@money($row['running'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Koi transaction nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>
