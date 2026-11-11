<x-app-layout>
    <x-slot name="title">Edit Overhead</x-slot>
    <x-slot name="header">Money Out · Dafter ka Kharcha Durust Karo (دفتری خرچ)</x-slot>

    @php
        $amountR = old('amount', \App\Support\Money::toRupees($overhead->amount_paisa));
    @endphp

    <form method="POST" action="{{ route('overheads.update', $overhead) }}" class="max-w-2xl">
        @csrf @method('PUT')
        <x-card title="Overhead Details — monthly (ماہانہ خرچ)">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="month" value="Month *" />
                    <x-text-input id="month" name="month" type="month" class="mt-1 block w-full" :value="old('month', $overhead->month)" required />
                    <x-input-error :messages="$errors->get('month')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="category" value="Category *" />
                    <x-text-input id="category" name="category" class="mt-1 block w-full" :value="old('category', $overhead->category)" placeholder="office_rent, salary_draw…" required />
                    <x-input-error :messages="$errors->get('category')" class="mt-1" />
                </div>

                <x-money-input name="amount" label="Amount (رقم)" :value="$amountR" required />

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $overhead->notes) }}</textarea>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-400">Overheads OVERALL profit kam karte hain (kisi ek project par nahi). Settings me pro-rata allocation toggle hai.</p>
        </x-card>

        <div class="mt-5 flex items-center gap-3">
            <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Update Overhead</button>
            <a href="{{ route('money-out') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</x-app-layout>
