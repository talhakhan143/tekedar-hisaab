<x-app-layout>
    <x-slot name="title">Edit Client Payment</x-slot>
    <x-slot name="header">Money In · Payment Durust Karo</x-slot>

    @php
        // Paise rupay me dikhte hain; paisa me badalna sirf controller ka kaam hai.
        $grossR = old('gross_amount', \App\Support\Money::toRupees($payment->gross_amount_paisa));
        $pickedProject = (string) old('project_id', $payment->project_id);
    @endphp

    <form method="POST" action="{{ route('client-payments.update', $payment) }}" class="max-w-2xl"
          x-data="{
            project: '{{ $pickedProject }}',
            gross: {{ (float) ($grossR ?: 0) }},
            fmt(n){ return '₨ ' + Number(n||0).toLocaleString('en-IN',{minimumFractionDigits:2, maximumFractionDigits:2}); }
          }">
        @csrf @method('PUT')
        <x-card title="Payment Details">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="project_id" value="Project *" />
                    <select id="project_id" name="project_id" x-model="project" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— select —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected($pickedProject === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                    @if($pickedProject !== '' && ! $projects->contains('id', (int) $pickedProject))
                        <p class="mt-1 text-xs text-red-500">Is payment ka purana project mojood nahi. Naya project chunna zaroori hai.</p>
                    @endif
                </div>
                <div>
                    <x-input-label for="date" value="Date *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', optional($payment->date)->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
                </div>

                <x-money-input name="gross_amount" label="Amount (کل رقم)" :value="$grossR" required x-model.number="gross" />

                <div>
                    <x-input-label for="payment_method" value="Method *" />
                    <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach(['cash','bank','cheque','online'] as $m)
                            <option value="{{ $m }}" @selected(old('payment_method', $payment->payment_method) === $m)>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-center gap-2">
                    <input type="hidden" name="is_mobilization" value="0">
                    <input type="checkbox" id="is_mobilization" name="is_mobilization" value="1"
                           @checked(old('is_mobilization', $payment->is_mobilization))
                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="is_mobilization" class="text-sm text-gray-700">Advance / Peshgi (kaam shuru hone se pehle li gayi rakam)</label>
                </div>

                <div>
                    <x-input-label for="reference" value="Bill No / Reference" />
                    <x-text-input id="reference" name="reference" class="mt-1 block w-full" :value="old('reference', $payment->reference)" />
                    <x-input-error :messages="$errors->get('reference')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $payment->notes) }}</textarea>
                </div>
            </div>

            <div class="mt-5 rounded-lg bg-gray-50 p-4 text-center text-sm">
                <div class="text-gray-500">Total Mila (کل وصولی)</div>
                <div class="text-lg font-bold text-emerald-600" x-text="fmt(gross)"></div>
            </div>
        </x-card>

        <div class="mt-5 flex items-center gap-3">
            <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Update Payment</button>
            <a href="{{ route('money-in') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</x-app-layout>
