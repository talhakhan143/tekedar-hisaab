<x-app-layout>
    <x-slot name="title">Estimates · {{ $project->name }}</x-slot>
    <x-slot name="header">Estimates · {{ $project->name }}</x-slot>

    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('projects.show', $project) }}" class="text-sm font-medium text-emerald-700 hover:underline">← Back to project</a>
        <span class="flex-1"></span>
        <span class="text-sm text-gray-500">Budget total:</span>
        <span class="text-lg font-bold text-gray-900">@money($estimateTotal)</span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Add form --}}
        <div class="lg:col-span-1">
            <x-card title="Add Estimate Line">
                <form method="POST" action="{{ route('estimates.store', $project) }}" class="space-y-4"
                      x-data="{ cat: 'material' }">
                    @csrf
                    <div>
                        <x-input-label for="category" value="Category *" />
                        <select id="category" name="category" x-model="cat" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($categories as $c)
                                <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="item_name" value="Item *" />
                        <x-text-input id="item_name" name="item_name" class="mt-1 block w-full" :value="old('item_name')" required />
                        <x-input-error :messages="$errors->get('item_name')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="qty_estimated" value="Qty *" />
                            <x-text-input id="qty_estimated" name="qty_estimated" type="number" step="0.001" class="mt-1 block w-full" :value="old('qty_estimated', 1)" required />
                        </div>
                        <div>
                            <x-input-label for="unit" value="Unit" />
                            <x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit')" placeholder="bag, kg, sqft…" />
                        </div>
                    </div>
                    <x-money-input name="rate_per_unit" label="Rate / unit *" :value="old('rate_per_unit')" required />
                    <div x-show="cat === 'material'">
                        <x-input-label for="wastage_percent" value="Wastage % (material)" />
                        <x-text-input id="wastage_percent" name="wastage_percent" type="number" step="0.01" class="mt-1 block w-full" :value="old('wastage_percent', $defaultWastage)" />
                        <p class="mt-1 text-xs text-gray-400">Expected qty auto-inflates by wastage.</p>
                    </div>
                    <button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Add Line</button>
                </form>
            </x-card>
        </div>

        {{-- Existing lines + variance --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Estimate vs Actual (by category)" class="!p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3 text-right">Estimated</th>
                                <th class="px-4 py-3 text-right">Actual</th>
                                <th class="px-4 py-3 text-right">Variance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($categories as $c)
                                @php $e = $estimateByCat[$c] ?? 0; $a = $actualByCat[$c] ?? 0; $var = $a - $e; @endphp
                                @if($e > 0 || $a > 0)
                                    <tr class="{{ $var > 0 && $e > 0 ? 'bg-red-50' : '' }}">
                                        <td class="px-4 py-2.5 capitalize font-medium text-gray-700">{{ $c }}</td>
                                        <td class="px-4 py-2.5 text-right text-gray-600">@money($e)</td>
                                        <td class="px-4 py-2.5 text-right text-gray-600">@money($a)</td>
                                        <td class="px-4 py-2.5 text-right font-semibold {{ $var > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $var > 0 ? '+' : '' }}@money($var)</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Line Items" class="!p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Cat</th>
                                <th class="px-4 py-3 text-right">Qty</th>
                                <th class="px-4 py-3 text-right">Rate</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($estimates->flatten() as $e)
                                <tr>
                                    <td class="px-4 py-2.5 font-medium text-gray-700">{{ $e->item_name }}
                                        @if($e->wastage_percent > 0)<span class="ml-1 text-xs text-amber-600">+{{ rtrim(rtrim($e->wastage_percent,'0'),'.') }}% wastage</span>@endif
                                    </td>
                                    <td class="px-4 py-2.5 capitalize text-gray-500">{{ $e->category }}</td>
                                    <td class="px-4 py-2.5 text-right">{{ rtrim(rtrim($e->qty_estimated,'0'),'.') }} {{ $e->unit }}</td>
                                    <td class="px-4 py-2.5 text-right">@money($e->rate_per_unit_paisa)</td>
                                    <td class="px-4 py-2.5 text-right font-medium">@money($e->amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <form method="POST" action="{{ route('estimates.destroy', $e) }}" onsubmit="return confirm('Line delete?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-500 hover:text-red-700">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi estimate line nahi. Left form se add karo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
