<x-app-layout>
    <x-slot name="title">Material Purchases</x-slot>
    <x-slot name="header">Material Purchases (مٹیریل خریداری)</x-slot>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="grid grid-cols-2 gap-4">
            <x-stat label="Total Material Spent (کل خرچ)" :value="\App\Support\Money::format($totalSpent)" color="text-amber-600" />
            <x-stat label="Outstanding — udhaar (باقی اُدھار)" :value="\App\Support\Money::format($totalDue)" color="text-red-600" />
        </div>
        <a href="{{ route('materials.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ New Purchase (نئی خریداری)</a>
    </div>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date (تاریخ)</th>
                        <th class="px-4 py-3">Item (چیز)</th>
                        <th class="px-4 py-3">Project (پروجیکٹ)</th>
                        <th class="px-4 py-3">Vendor (سپلائر)</th>
                        <th class="px-4 py-3 text-right">Amount (رقم)</th>
                        <th class="px-4 py-3 text-right">Paid (دیا)</th>
                        <th class="px-4 py-3 text-right">Balance (باقی)</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchases as $pur)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5">{{ $pur->date->format('d-m-Y') }}</td>
                            <td class="px-4 py-2.5 font-medium text-gray-700">{{ $pur->item_name }}<div class="text-xs text-gray-400">{{ rtrim(rtrim($pur->qty,'0'),'.') }} {{ $pur->unit }}</div></td>
                            <td class="px-4 py-2.5">{{ $pur->project->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">{{ $pur->vendor->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right">@money($pur->amount_paisa)</td>
                            <td class="px-4 py-2.5 text-right text-emerald-600">@money($pur->amount_paid_paisa)</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $pur->balance_due_paisa > 0 ? 'text-red-600' : 'text-gray-400' }}">@money($pur->balance_due_paisa)</td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('materials.edit', $pur) }}" class="text-gray-400 hover:text-gray-700">Edit</a>
                                <form method="POST" action="{{ route('materials.destroy', $pur) }}" class="ml-2 inline" onsubmit="return confirm('Delete purchase?')">
                                    @csrf @method('DELETE')<button class="text-red-500 hover:text-red-700">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Koi purchase nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())<div class="border-t border-gray-100 p-3">{{ $purchases->links() }}</div>@endif
    </x-card>
</x-app-layout>
