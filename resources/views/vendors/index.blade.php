@php
    /*
     * Ye list ab teen qism mila kar dikhati hai: supplier, thekedaar aur driver.
     * Filter ki options Vendor::TYPES se aati hain aur badge ka naam
     * $v->typeLabel() se, taake nayi qism add karne par ye safa khud chal jaye.
     */
    $activeType = (string) ($filters['type'] ?? request('type', ''));

    $typeColor = fn ($t) => match ($t) {
        'subcontractor' => 'indigo',
        'driver'        => 'amber',
        default         => 'gray',
    };
@endphp
<x-app-layout>
    <x-slot name="title">Vendors</x-slot>
    <x-slot name="header">Vendors (سپلائر، ٹھیکیدار، ڈرائیور)</x-slot>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-stat label="Total Payable — udhaar (کل اُدھار)" :value="\App\Support\Money::format($totalPayable)" color="text-red-600" />
        <a href="{{ route('vendors.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ New Vendor (نیا سپلائر)</a>
    </div>

    {{-- Qism ka filter. Projects list wala hi tareeqa: saada GET form, chuni hui
         value wapis select ho jati hai, aur pagination controller ki
         withQueryString() se query string saath le kar chalta hai. --}}
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="type" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All types (سب)</option>
            @foreach(\App\Models\Vendor::TYPES as $tValue => $tLabel)
                <option value="{{ $tValue }}" @selected($activeType === (string) $tValue)>{{ $tLabel }}</option>
            @endforeach
        </select>
        <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>
        @if($activeType !== '')
            <a href="{{ route('vendors.index') }}" class="self-center text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name (نام)</th>
                        <th class="px-4 py-3">Type (قسم)</th>
                        <th class="px-4 py-3">Phone (فون)</th>
                        <th class="px-4 py-3 text-right">Kiraya (کرایہ)</th>
                        <th class="px-4 py-3 text-right">Payable (اُدھار)</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vendors as $v)
                        @php
                            $payable  = (int) $v->opening_balance_paisa + (int) ($v->balance_sum ?? 0);
                            $isDriver = $v->type === 'driver';
                            // Driver ka kiraya. Agar controller ne withSum se pehle hi
                            // jor bhej diya ho to wahi istemal hota hai (ek hi query),
                            // warna har driver row apna jor khud nikal leti hai.
                            $kiraya = $isDriver ? (int) ($v->freight_sum ?? $v->freightPaisa()) : 0;
                            // Driver ko kiraya me se kitna diya, aur kitna baqi.
                            $kDiya = $isDriver ? (int) ($v->driver_paid_sum ?? $v->driverPaidPaisa()) : 0;
                            $kBaqi = $kiraya - $kDiya;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5"><a href="{{ route('vendors.show', $v) }}" class="font-medium text-emerald-700 hover:underline">{{ $v->name }}</a></td>
                            <td class="px-4 py-2.5"><x-badge :color="$typeColor($v->type)">{{ $v->typeLabel() }}</x-badge></td>
                            <td class="px-4 py-2.5 text-gray-500">{{ $v->phone }}</td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                @if($isDriver)
                                    <span class="font-semibold text-gray-700">@money($kiraya)</span>
                                    <div class="text-[11px] font-normal text-emerald-600">diya @money($kDiya)</div>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right font-semibold whitespace-nowrap">
                                @if($isDriver)
                                    {{-- Driver ka dena = baqi kiraya + purana opening balance. Jis
                                         purchase par driver laga ho us ka kiraya supplier ke udhaar se
                                         nikal chuka hota hai, is liye ye raqam dobara nahi ginti. --}}
                                    @php $kTotal = $kBaqi + $payable; @endphp
                                    <span class="{{ $kTotal > 0 ? 'text-red-600' : ($kTotal < 0 ? 'text-amber-600' : 'text-gray-400') }}">@money($kTotal)</span>
                                    @if($kBaqi < 0)<div class="text-[11px] font-normal text-amber-600">zyada diya</div>@endif
                                @else
                                    <span class="{{ $payable > 0 ? 'text-red-600' : 'text-gray-400' }}">@money($payable)</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right"><a href="{{ route('vendors.edit', $v) }}" class="text-gray-400 hover:text-gray-700">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Koi vendor nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vendors->hasPages())<div class="border-t border-gray-100 p-3">{{ $vendors->links() }}</div>@endif
    </x-card>
</x-app-layout>
