<x-app-layout>
    <x-slot name="title">Kiraya Edit</x-slot>
    <x-slot name="header">Kiraya Payment Edit (کرایہ)</x-slot>

    {{-- Kaun sa driver aur kaun si entry, taake ghalat row edit na ho jaye. --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ route('vendors.show', $driver) }}" class="text-sm font-medium text-emerald-700 hover:underline">← {{ $driver->name }}</a>
        <x-badge color="amber">Kiraya #{{ $payment->id }}</x-badge>
        <x-badge color="indigo">{{ $payment->date->format('d-m-Y') }}</x-badge>
        <span class="text-sm text-gray-500">
            Abhi record: @money($payment->amount_paisa)
            · {{ $payment->project?->name ?? 'bina project' }}
        </span>
    </div>

    <form method="POST" action="{{ route('driver-payments.update', $payment) }}" class="max-w-3xl" x-data="{ override: false }">
        @csrf @method('PUT')

        <x-card title="Kiraya do (کرایہ)">
            <div class="mb-5 rounded-md bg-amber-50 px-3 py-2 text-center text-sm">
                Is entry ko wapis jama kar ke baqi kiraya: <span class="font-bold text-amber-700">@money($capPaisa)</span>
            </div>
            <p class="mb-5 text-xs text-gray-400">
                Driver ke page par likhe baqi me ye entry pehle se minus ho chuki hai, is liye yahan usay wapis jama kiya gaya hai.
                Isi wajah se purani rakam dobara save karna "zyada diya" nahi banta.
            </p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="project_id" value="Project (پروجیکٹ)" />
                    <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— bina project —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected((string) old('project_id', $payment->project_id) === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date" value="Date (تاریخ) *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full"
                                  :value="old('date', $payment->date->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
                </div>

                <x-money-input name="amount" label="Amount (رقم) *" required
                               :value="old('amount', \App\Support\Money::toRupees($payment->amount_paisa))"
                               :help="'Pehle is entry par ' . \App\Support\Money::format($payment->amount_paisa) . ' tha.'" />

                <div>
                    <x-input-label for="payment_method" value="Kaise diya (طریقہ)" />
                    <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach(['cash' => 'Cash', 'bank' => 'Bank', 'cheque' => 'Cheque', 'online' => 'Online'] as $k => $label)
                            <option value="{{ $k }}" @selected(old('payment_method', $payment->payment_method) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('payment_method')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="hidden" name="override" value="0">
                        <input type="checkbox" name="override" value="1" x-model="override" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        Override (baqi se zyada dena hai)
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes (نوٹ)" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $payment->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>
            </div>
        </x-card>

        <div class="mt-5 flex items-center gap-3">
            <button class="rounded-md bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">Update Kiraya</button>
            <a href="{{ route('vendors.show', $driver) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</x-app-layout>
