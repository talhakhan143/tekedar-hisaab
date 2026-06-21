<x-app-layout>
    <x-slot name="title">New Client Payment</x-slot>
    <x-slot name="header">Money In · New Client Payment</x-slot>

    @php
        $projData = $projects->mapWithKeys(fn($p) => [$p->id => (float) $p->retention_percent])->toJson();
    @endphp

    <form method="POST" action="{{ route('client-payments.store') }}" class="max-w-2xl"
          x-data="{
            projects: {{ $projData }},
            project: '{{ $selectedProject ?? '' }}',
            gross: 0,
            mob: false,
            retentionEdited: false,
            retention: 0,
            get pct(){ return this.projects[this.project] ?? 0; },
            get autoRetention(){ return this.mob ? 0 : Math.round(this.gross * this.pct) / 100; },
            get net(){ return Math.max(0, this.gross - (this.retentionEdited ? this.retention : this.autoRetention)); },
            fmt(n){ return '₨ ' + Number(n||0).toLocaleString('en-PK',{minimumFractionDigits:2, maximumFractionDigits:2}); }
          }">
        @csrf
        <x-card title="Payment Details">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="project_id" value="Project *" />
                    <select id="project_id" name="project_id" x-model="project" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— select —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ rtrim(rtrim($p->retention_percent,'0'),'.') }}% ret.)</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date" value="Date *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                </div>

                <x-money-input name="gross_amount" label="Gross Amount" :value="old('gross_amount')" required x-model.number="gross" />

                <div>
                    <x-input-label for="payment_method" value="Method *" />
                    <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach(['cash','bank','cheque','online'] as $m)
                            <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-center gap-2">
                    <input type="hidden" name="is_mobilization" value="0">
                    <input type="checkbox" id="is_mobilization" name="is_mobilization" value="1" x-model="mob"
                           class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="is_mobilization" class="text-sm text-gray-700">Mobilization advance (peshgi before work — no retention, not counted as profit twice)</label>
                </div>

                <div>
                    <x-input-label for="retention_held" value="Retention Held (auto, editable)" />
                    <div class="relative mt-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">₨</span>
                        <input type="number" step="0.01" min="0" id="retention_held" name="retention_held"
                               x-model.number="retention"
                               x-effect="if(!retentionEdited){ retention = autoRetention }"
                               @input="retentionEdited = true"
                               class="block w-full rounded-md border-gray-300 pl-8 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Auto = gross × project retention%. Override if needed.</p>
                </div>

                <div>
                    <x-input-label for="reference" value="Bill No / Reference" />
                    <x-text-input id="reference" name="reference" class="mt-1 block w-full" :value="old('reference')" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-3 gap-3 rounded-lg bg-gray-50 p-4 text-center text-sm">
                <div><div class="text-gray-500">Gross</div><div class="font-bold text-gray-900" x-text="fmt(gross)"></div></div>
                <div><div class="text-gray-500">Retention</div><div class="font-bold text-amber-600" x-text="fmt(retentionEdited ? retention : autoRetention)"></div></div>
                <div><div class="text-gray-500">Net Received</div><div class="font-bold text-emerald-600" x-text="fmt(net)"></div></div>
            </div>
        </x-card>

        <div class="mt-5 flex items-center gap-3">
            <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save Payment</button>
            <a href="{{ route('money-in') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</x-app-layout>
