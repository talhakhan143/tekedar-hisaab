<x-app-layout>
    <x-slot name="title">Haazri Edit</x-slot>
    <x-slot name="header">Haazri / Work Edit (حاضری)</x-slot>

    {{-- Kaun sa banda aur kaun si entry, taake ghalat row edit na ho jaye. --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ route('workers.show', $worker) }}" class="text-sm font-medium text-emerald-700 hover:underline">← {{ $worker->name }}</a>
        <x-badge color="gray">Entry #{{ $entry->id }}</x-badge>
        <x-badge color="indigo">{{ $entry->date->format('d-m-Y') }}</x-badge>
        <span class="text-sm text-gray-500">
            Abhi record: @money($entry->computed_wage_paisa)
            · {{ $entry->project?->name ?? 'bina project' }}
            · {{ str_replace('contract_piece', 'piece', $worker->wage_type) }}
        </span>
    </div>

    @php
        $isPiece = $worker->wage_type === 'contract_piece';
    @endphp

    <form method="POST" action="{{ route('work-entries.update', $entry) }}" class="max-w-3xl"
          x-data="{
              isPiece: {{ $isPiece ? 'true' : 'false' }},
              days: @js((string) old('days_present', $entry->days_present)),
              units: @js((string) old('units_done', $entry->units_done)),
              rate: @js((string) old('dihaadi_rate', \App\Support\Money::toRupees($ratePaisa))),
              num(v) { const n = Number(String(v).replace(/[^\d.\-]/g, '')); return isFinite(n) ? n : 0 },
              get qty() { return this.num(this.isPiece ? this.units : this.days) },
              get wage() { return Math.round(this.qty * this.num(this.rate) * 100) / 100 },
              get wageText() { return this.wage.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
          }"
          x-on:input="
              if ($event.target.name === 'days_present') days = $event.target.value;
              if ($event.target.name === 'units_done') units = $event.target.value;
              if ($event.target.name === 'dihaadi_rate') rate = $event.target.value;
          ">
        @csrf @method('PUT')

        <x-card title="Attendance / Work (حاضری)">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="project_id" value="Project (پروجیکٹ)" />
                    <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— no project —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected((string) old('project_id', $entry->project_id) === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date" value="Date (تاریخ) *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full"
                                  :value="old('date', $entry->date->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="days_present" value="Days present (حاضری دن)" />
                    <x-text-input id="days_present" name="days_present" type="number" step="0.01" min="0" max="31"
                                  class="mt-1 block w-full" :value="old('days_present', $entry->days_present)" />
                    <p class="mt-1 text-xs text-gray-400">0.5 = aadha din. {{ $isPiece ? 'Is bande ka hisaab units par hai, din sirf record ke liye.' : 'Wage inhi dinon par banti hai.' }}</p>
                    <x-input-error :messages="$errors->get('days_present')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="units_done" value="Units done (کام کی مقدار)" />
                    <x-text-input id="units_done" name="units_done" type="number" step="0.001" min="0"
                                  class="mt-1 block w-full" :value="old('units_done', $entry->units_done)" />
                    <p class="mt-1 text-xs text-gray-400">{{ $isPiece ? 'Wage inhi units par banti hai.' : 'Piece wale bande ke liye hota hai, is bande ka hisaab dinon par hai.' }}</p>
                    <x-input-error :messages="$errors->get('units_done')" class="mt-1" />
                </div>

                <x-money-input name="dihaadi_rate" label="Dihaadi rate (is entry ke liye) *" required
                               :value="old('dihaadi_rate', \App\Support\Money::toRupees($ratePaisa))"
                               :help="'Yehi rate is entry me jamaa hua tha. Bande ki aaj ki dihaadi ' . \App\Support\Money::format($worker->default_wage_paisa) . ' hai; usay badalna ho to Worker edit se badlo, yahan se purana hisaab nahi badalta.'" />

                <div class="sm:col-span-2">
                    <div class="rounded-md bg-indigo-50 px-3 py-2 text-center text-sm">
                        {{ $isPiece ? 'Units' : 'Din' }} <span class="font-bold text-indigo-700" x-text="qty"></span>
                        × rate <span class="font-bold text-indigo-700">₨ <span x-text="num(rate).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></span>
                        = wage <span class="font-bold text-indigo-700">₨ <span x-text="wageText"></span></span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Pehle is entry par @money($entry->computed_wage_paisa) likha tha. Save karte hi upar wala number lag jayega, koi chhupa hisaab nahi.</p>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes (نوٹ)" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $entry->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>
            </div>
        </x-card>

        <div class="mt-5 flex items-center gap-3">
            <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Update Entry</button>
            <a href="{{ route('workers.show', $worker) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</x-app-layout>
