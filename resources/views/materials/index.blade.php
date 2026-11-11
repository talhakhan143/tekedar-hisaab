<x-app-layout>
    <x-slot name="title">Material Purchases</x-slot>
    <x-slot name="header">Material Purchases (مٹیریل خریداری)</x-slot>

    @php
        // Kiraya / mazdoori / deegar ke kul totals. Agar kahin koi charge hi na laga
        // ho to ye khana bilkul nahi dikhta, purani rows saaf nazar aati hain.
        $sumFreight = (int) ($totalFreight ?? 0);
        $sumLabour  = (int) ($totalLabour ?? 0);
        $sumOther   = (int) ($totalOtherCharges ?? 0);
        $sumCharges = $sumFreight + $sumLabour + $sumOther;

        $chargeBits = [];
        if ($sumFreight > 0) { $chargeBits[] = 'Kiraya ' . \App\Support\Money::format($sumFreight, false, false); }
        if ($sumLabour > 0)  { $chargeBits[] = 'Mazdoori ' . \App\Support\Money::format($sumLabour, false, false); }
        if ($sumOther > 0)   { $chargeBits[] = 'Deegar ' . \App\Support\Money::format($sumOther, false, false); }
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="grid grid-cols-2 gap-4 {{ $sumCharges > 0 ? 'sm:grid-cols-3' : '' }}">
            <x-stat label="Total Material Spent (کل خرچ)" :value="\App\Support\Money::format($totalSpent)" color="text-amber-600"
                    :sub="$sumCharges > 0 ? 'Maal aur charges, dono shamil' : null" />
            <x-stat label="Outstanding — udhaar (باقی اُدھار)" :value="\App\Support\Money::format($totalDue)" color="text-red-600" />
            @if ($sumCharges > 0)
                <x-stat label="Upar lage charges (اوپری خرچ)" :value="\App\Support\Money::format($sumCharges)" color="text-sky-600"
                        :sub="implode(' · ', $chargeBits)" />
            @endif
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
                        @php
                            // Amount column grand total hi dikhata hai; charges ki tafseel
                            // neeche chhoti line me jati hai, taake phone par table chaura
                            // na ho. Charge na ho to line bhi nahi.
                            $rowFreight = (int) $pur->freight_paisa;
                            $rowLabour  = (int) $pur->labour_paisa;
                            $rowOther   = (int) $pur->other_charges_paisa;
                            $rowExtra   = $rowFreight + $rowLabour + $rowOther;
                            // Purani rows (jin par ye columns bhare hi nahi) me goods 0 hota
                            // hai, to maal ki raqam grand total se charges nikal kar banti hai.
                            $rowGoods   = (int) $pur->goods_amount_paisa ?: ((int) $pur->amount_paisa - $rowExtra);

                            $rowBits = [];
                            if ($rowExtra > 0) {
                                $rowBits[] = 'Maal ' . \App\Support\Money::format($rowGoods, false, false);
                                if ($rowFreight > 0) { $rowBits[] = 'Kiraya ' . \App\Support\Money::format($rowFreight, false, false); }
                                if ($rowLabour > 0)  { $rowBits[] = 'Mazdoori ' . \App\Support\Money::format($rowLabour, false, false); }
                                if ($rowOther > 0)   { $rowBits[] = 'Deegar ' . \App\Support\Money::format($rowOther, false, false); }
                            }
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5">{{ $pur->date->format('d-m-Y') }}</td>
                            <td class="px-4 py-2.5 font-medium text-gray-700">{{ $pur->item_name }}<div class="text-xs text-gray-400">{{ rtrim(rtrim($pur->qty,'0'),'.') }} {{ $pur->unit }}</div>@if ($rowBits)<div class="text-xs text-sky-600">{{ implode(' + ', $rowBits) }}</div>@endif</td>
                            <td class="px-4 py-2.5">{{ $pur->project->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">{{ $pur->vendor->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-gray-900">@money($pur->amount_paisa)</td>
                            <td class="px-4 py-2.5 text-right text-emerald-600">@money($pur->amount_paid_paisa)</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $pur->balance_due_paisa > 0 ? 'text-red-600' : 'text-gray-400' }}">@money($pur->balance_due_paisa)</td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('vouchers.material', $pur) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800">🖨 Invoice</a>
                                <a href="{{ route('materials.edit', $pur) }}" class="ml-2 text-gray-400 hover:text-gray-700">Edit</a>
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
