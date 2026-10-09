@php
    $isPerSqft = old('pricing_mode', $project->pricing_mode) === 'per_sqft';
    $rateRupees = old('rate_per_sqft', $project->rate_per_sqft_paisa ? \App\Support\Money::toRupees($project->rate_per_sqft_paisa) : '');
    $valueRupees = old('contract_value', $project->contract_value_paisa ? \App\Support\Money::toRupees($project->contract_value_paisa) : '');
@endphp

<div x-data="{
        mode: '{{ old('pricing_mode', $project->pricing_mode) }}',
        area: {{ (float) old('covered_area_sqft', $project->covered_area_sqft ?? 0) }},
        rate: {{ (float) ($rateRupees ?: 0) }},
        get computed() { return (this.area * this.rate) || 0; },
        fmt(n){ return '₨ ' + Number(n).toLocaleString('en-PK',{maximumFractionDigits:0}); }
     }" class="space-y-6">

    <x-card title="Client & Contract">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Project Name *" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $project->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="client_name" value="Client Name" />
                <x-text-input id="client_name" name="client_name" class="mt-1 block w-full" :value="old('client_name', $project->client_name)" />
            </div>
            <div>
                <x-input-label for="client_phone" value="Client Phone" />
                <x-text-input id="client_phone" name="client_phone" class="mt-1 block w-full" :value="old('client_phone', $project->client_phone)" />
            </div>
            <div>
                <x-input-label for="client_address" value="Client Address" />
                <x-text-input id="client_address" name="client_address" class="mt-1 block w-full" :value="old('client_address', $project->client_address)" />
            </div>
        </div>
    </x-card>

    <x-card title="Pricing">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="contract_type" value="Contract Type *" />
                <select id="contract_type" name="contract_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach (\App\Models\Project::CONTRACT_TYPES as $ctValue => $ctLabel)
                        <option value="{{ $ctValue }}" @selected(old('contract_type',$project->contract_type)===$ctValue)>{{ $ctLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="pricing_mode" value="Pricing Mode *" />
                <select id="pricing_mode" name="pricing_mode" x-model="mode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="per_sqft">Per sq.ft</option>
                    <option value="lump_sum">Lump sum</option>
                </select>
            </div>

            {{-- Per sqft fields --}}
            <template x-if="mode === 'per_sqft'">
                <div class="grid grid-cols-1 gap-5 sm:col-span-2 sm:grid-cols-3">
                    <div>
                        <x-input-label for="covered_area_sqft" value="Covered Area (sq.ft) *" />
                        <x-text-input id="covered_area_sqft" name="covered_area_sqft" type="number" step="0.01" x-model.number="area" class="mt-1 block w-full" :value="old('covered_area_sqft', $project->covered_area_sqft)" />
                        <x-input-error :messages="$errors->get('covered_area_sqft')" class="mt-1" />
                    </div>
                    <div>
                        <x-money-input name="rate_per_sqft" label="Rate / sq.ft *" :value="$rateRupees" x-model.number="rate" />
                    </div>
                    <div>
                        <x-input-label value="Contract Value (auto)" />
                        <div class="mt-1 rounded-md bg-emerald-50 px-3 py-2 text-lg font-bold text-emerald-700" x-text="fmt(computed)"></div>
                    </div>
                </div>
            </template>

            {{-- Lump sum field --}}
            <template x-if="mode === 'lump_sum'">
                <div class="sm:col-span-2 sm:w-1/2">
                    <x-money-input name="contract_value" label="Contract Value (lump sum) *" :value="$valueRupees" />
                </div>
            </template>

            @if (! $project->exists)
                <div>
                    <x-money-input name="advance_amount" label="Advance / Peshgi"
                        help="Client ne shuru me jo peshgi di. Ye Money In me khud chali jayegi, alag se entry karne ki zaroorat nahi." />
                </div>
            @endif
        </div>
    </x-card>

    <x-card title="Schedule & Status">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
            <div>
                <x-input-label for="start_date" value="Start Date" />
                <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', optional($project->start_date)->format('Y-m-d'))" />
            </div>
            <div>
                <x-input-label for="expected_end_date" value="Expected End" />
                <x-text-input id="expected_end_date" name="expected_end_date" type="date" class="mt-1 block w-full" :value="old('expected_end_date', optional($project->expected_end_date)->format('Y-m-d'))" />
            </div>
            <div>
                <x-input-label for="actual_end_date" value="Actual End" />
                <x-text-input id="actual_end_date" name="actual_end_date" type="date" class="mt-1 block w-full" :value="old('actual_end_date', optional($project->actual_end_date)->format('Y-m-d'))" />
            </div>
            <div>
                <x-input-label for="status" value="Status *" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach (['quoted','active','on_hold','completed','closed'] as $s)
                        <option value="{{ $s }}" @selected(old('status',$project->status)===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-4">
                <x-input-label for="notes" value="Notes" />
                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $project->notes) }}</textarea>
            </div>
        </div>
    </x-card>

    <div class="flex items-center gap-3">
        <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ $submit ?? 'Save' }}</button>
        <a href="{{ route('projects.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
    </div>
</div>
