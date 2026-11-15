@php
    /*
     * Qism ki list Vendor::TYPES se aati hai, yahan hardcode nahi.
     * Pehle is file me sirf do <option> likhi hui thin (supplier, subcontractor),
     * is liye 'driver' (ڈرائیور) add karne ke liye model ke saath ye form bhi
     * badalna parta tha. Ab model me ek qism barhao, form khud utha lega.
     */
    $currentType = old('type', $vendor->type ?? 'supplier');
@endphp
<x-card title="Vendor Details">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $vendor->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="type" value="Type *" />
            <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @foreach(\App\Models\Vendor::TYPES as $tValue => $tLabel)
                    <option value="{{ $tValue }}" @selected((string) $currentType === (string) $tValue)>{{ $tLabel }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('type')" class="mt-1" />
            <p class="mt-1 text-xs text-gray-400">Driver (ڈرائیور) ek hi bar yahan add karo, phir har project me kiraya ke saath select ho jayega.</p>
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $vendor->phone)" />
        </div>
        <x-money-input name="opening_balance" label="Opening Balance (udhaar we owe)"
                       :value="old('opening_balance', $vendor->opening_balance_paisa ? \App\Support\Money::toRupees($vendor->opening_balance_paisa) : '')"
                       step="0.01" />
        <div class="sm:col-span-2">
            <x-input-label for="notes" value="Notes" />
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $vendor->notes) }}</textarea>
        </div>
    </div>
</x-card>
<div class="mt-5 flex items-center gap-3">
    <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ $submit ?? 'Save' }}</button>
    <a href="{{ route('vendors.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
</div>
