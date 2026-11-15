@php
    $isDriver  = $vendor->type === 'driver';
    $typeColor = match ($vendor->type) {
        'subcontractor' => 'indigo',
        'driver'        => 'amber',
        default         => 'gray',
    };

    /*
     * Driver ke chakkar. trips() wo khareedariyan deti hai jin par ye driver laga
     * tha (material_purchases.driver_id). LiveProjectScope khud lagti hai, is liye
     * mitaye gaye project ki rows yahan nahi aati.
     *
     * Total yahin rows se jor kar banta hai, freightPaisa() se nahi, taake neeche
     * likha total upar ki lines se hamesha mile. Do alag jagah se aane wali raqam
     * kabhi na kabhi farq dikha deti hai aur maalik ka bharosa khatam ho jata hai.
     */
    $trips      = collect();
    $tripTotal  = 0;
    $kirayaPays = collect();
    $kirayaDiya = 0;
    $kirayaBaqi = 0;
    if ($isDriver) {
        $trips      = $vendor->trips()->with('project')->orderBy('date')->get();
        $tripTotal  = (int) $trips->sum(fn ($p) => (int) $p->freight_paisa);
        $kirayaPays = $vendor->driverPayments()->with('project')->orderByDesc('date')->orderByDesc('id')->get();
        $kirayaDiya = (int) $kirayaPays->sum(fn ($p) => (int) $p->amount_paisa);
        $kirayaBaqi = $tripTotal - $kirayaDiya;
    }
@endphp
<x-app-layout>
    <x-slot name="title">{{ $vendor->name }}</x-slot>
    <x-slot name="header">{{ $vendor->name }}</x-slot>

    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('vendors.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Vendors</a>
        <x-badge :color="$typeColor">{{ $vendor->typeLabel() }}</x-badge>
        <span class="text-sm text-gray-500">{{ $vendor->phone }}</span>
        <span class="flex-1"></span>
        <a href="{{ route('vendors.edit', $vendor) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $isDriver ? 'xl:grid-cols-4' : '' }}">
        @if($isDriver)
            {{-- Driver ka hisaab mazdoor jaisa hai: kul kiraya bana, us me se
                 jo diya, aur baqi. Jis purchase par driver laga hota hai us ka
                 kiraya supplier ke udhaar se nikal dia jata hai, is liye ye
                 baqi kisi doosri jagah dobara nahi ginti. --}}
            <x-stat label="Kul kiraya bana (کل کرایہ)" :value="\App\Support\Money::format($tripTotal)" color="text-gray-700"
                    :sub="$trips->count().' chakkar'" />
            <x-stat label="Diya (دیا)" :value="\App\Support\Money::format($kirayaDiya)" color="text-emerald-600"
                    :sub="$kirayaPays->count().' payment'" />
            <x-stat label="Baqi kiraya (باقی)" :value="\App\Support\Money::format($kirayaBaqi)"
                    :color="$kirayaBaqi > 0 ? 'text-red-600' : ($kirayaBaqi < 0 ? 'text-amber-600' : 'text-gray-400')"
                    :sub="$kirayaBaqi < 0 ? 'Zyada diya hua hai (advance)' : 'Kul kiraya minus jo diya'" />
            <x-stat label="Purana udhaar (اُدھار)" :value="\App\Support\Money::format($payable)"
                    :color="$payable > 0 ? 'text-red-600' : 'text-gray-400'"
                    sub="Opening balance / seedha bill. Kiraya is me nahi." />
        @else
            <x-stat label="Total Payable (udhaar)" :value="\App\Support\Money::format($payable)" :color="$payable > 0 ? 'text-red-600' : 'text-gray-400'" />
            <x-stat label="Opening Balance" :value="\App\Support\Money::format($vendor->opening_balance_paisa)" />
        @endif
    </div>

    @if($isDriver)
        <x-card title="Kiraya ke chakkar (کرایہ)" class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Date (تاریخ)</th>
                            <th class="px-4 py-3">Project (پروجیکٹ)</th>
                            <th class="px-4 py-3">Item (چیز)</th>
                            <th class="px-4 py-3 text-right">Kiraya (کرایہ)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($trips as $trip)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 whitespace-nowrap">{{ optional($trip->date)->format('d-m-Y') ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-gray-700">{{ $trip->project->name ?? '— general —' }}</td>
                                <td class="px-4 py-2.5 text-gray-700">{{ $trip->item_name }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-amber-600">@money($trip->freight_paisa)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Is driver ka koi chakkar record nahi. Maal add karte waqt Kiraya ke saath driver select karo.</td></tr>
                        @endforelse
                    </tbody>
                    @if($trips->isNotEmpty())
                        <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-700" colspan="3">Kul kiraya (کل کرایہ) · {{ $trips->count() }} chakkar</td>
                                <td class="px-4 py-3 text-right text-base font-bold text-amber-600">@money($tripTotal)</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-card>
    @endif

    @if($isDriver)
        <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <x-card title="Kiraya do (کرایہ دیں)">
                    <form method="POST" action="{{ route('driver-payments.store', $vendor) }}" class="space-y-3">
                        @csrf
                        <div class="rounded-md bg-amber-50 px-3 py-2 text-center text-sm">
                            Baqi kiraya: <span class="font-bold text-amber-700">@money($kirayaBaqi)</span>
                        </div>
                        <div>
                            <x-input-label for="kp_project" value="Project (پروجیکٹ)" />
                            <select id="kp_project" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">— bina project —</option>
                                @foreach(\App\Models\Project::orderBy('name')->get(['id','name']) as $pr)
                                    <option value="{{ $pr->id }}" @selected(old('project_id') == $pr->id)>{{ $pr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="kp_date" value="Date (تاریخ) *" />
                            <x-text-input id="kp_date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                        </div>
                        <x-money-input name="amount" label="Amount (رقم) *" required :value="old('amount')" />
                        <div>
                            <x-input-label for="kp_method" value="Kaise diya (طریقہ)" />
                            <select id="kp_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach(['cash' => 'Cash', 'bank' => 'Bank', 'cheque' => 'Cheque', 'online' => 'Online'] as $k => $lbl)
                                    <option value="{{ $k }}" @selected(old('payment_method','cash') === $k)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input type="hidden" name="override" value="0">
                            <input type="checkbox" name="override" value="1" class="rounded border-gray-300 text-emerald-600">
                            Override (baqi se zyada dena hai)
                        </label>
                        <x-input-error :messages="$errors->get('amount')" />
                        <button class="w-full rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">Kiraya do</button>
                    </form>
                </x-card>
            </div>
            <div class="lg:col-span-2">
                <x-card title="Kiraya jo diya (ادائیگیاں)" class="!p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Date (تاریخ)</th>
                                    <th class="px-4 py-3">Project (پروجیکٹ)</th>
                                    <th class="px-4 py-3">Kaise</th>
                                    <th class="px-4 py-3 text-right">Diya (دیا)</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($kirayaPays as $kp)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 whitespace-nowrap">{{ optional($kp->date)->format('d-m-Y') }}</td>
                                        <td class="px-4 py-2.5 text-gray-700">{{ $kp->project->name ?? 'bina project' }}</td>
                                        <td class="px-4 py-2.5 text-gray-500">{{ ucfirst($kp->payment_method ?? 'cash') }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-emerald-600">@money($kp->amount_paisa)</td>
                                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                            <a href="{{ route('vouchers.driver-payment', $kp) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800" title="Print voucher">&#128424;</a>
                                            <a href="{{ route('driver-payments.edit', $kp) }}" class="ml-1 text-sky-600 hover:text-sky-800" title="Edit">&#9999;&#65039;</a>
                                            <form method="POST" action="{{ route('driver-payments.destroy', $kp) }}" class="ml-1 inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">&#10005;</button></form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Is driver ko abhi koi kiraya nahi diya.</td></tr>
                                @endforelse
                            </tbody>
                            @if($kirayaPays->isNotEmpty())
                                <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-gray-700" colspan="3">Kul diya</td>
                                        <td class="px-4 py-3 text-right text-base font-bold text-emerald-600">@money($kirayaDiya)</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    @endif

    {{-- Supplier / thekedaar ka ledger jaisa tha waisa hi. Driver par ye sirf tab
         dikhta hai jab waqai koi udhaar ya seedha bill mojood ho, warna khali
         table bila wajah jagah khata hai. --}}
    @if(! $isDriver || count($ledger) > 0)
        <x-card title="Ledger" class="{{ $isDriver ? 'mt-5 !p-0' : '!p-0' }}">
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
    @endif
</x-app-layout>
