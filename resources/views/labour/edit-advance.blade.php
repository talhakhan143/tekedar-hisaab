<x-app-layout>
    <x-slot name="title">Peshgi Edit</x-slot>
    <x-slot name="header">Advance / Recovery Edit (پیشگی / وصولی)</x-slot>

    {{-- Kaun sa banda aur kaun si row, taake ghalat entry edit na ho jaye. --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ route('workers.show', $worker) }}" class="text-sm font-medium text-emerald-700 hover:underline">← {{ $worker->name }}</a>
        <x-badge color="gray">Record #{{ $advance->id }}</x-badge>
        <x-badge :color="$advance->type === 'advance_given' ? 'amber' : 'emerald'">{{ $advance->type === 'advance_given' ? 'Given' : 'Recovery' }}</x-badge>
        <x-badge color="indigo">{{ $advance->date->format('d-m-Y') }}</x-badge>
        <span class="text-sm text-gray-500">
            Abhi record: @money($advance->amount_paisa) · {{ $advance->project?->name ?? 'bina project' }}
        </span>
    </div>

    <form method="POST" action="{{ route('worker-advances.update', $advance) }}" class="max-w-3xl">
        @csrf @method('PUT')

        <x-card title="Peshgi / Wasooli (پیشگی / وصولی)">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="type" value="Type (قسم) *" />
                    <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="advance_given" @selected(old('type', $advance->type) === 'advance_given')>Advance Given (پیشگی دی)</option>
                        <option value="recovery" @selected(old('type', $advance->type) === 'recovery')>Recovery (واپس وصول)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Given se payable kam hoti hai, Recovery se wapis barhti hai.</p>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="project_id" value="Project (پروجیکٹ)" />
                    <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— no project —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected((string) old('project_id', $advance->project_id) === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date" value="Date (تاریخ) *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full"
                                  :value="old('date', $advance->date->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
                </div>

                <x-money-input name="amount" label="Amount (رقم) *" required
                               :value="old('amount', \App\Support\Money::toRupees($advance->amount_paisa))"
                               :help="'Pehle is row par ' . \App\Support\Money::format($advance->amount_paisa) . ' tha.'" />

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes (نوٹ)" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $advance->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>
            </div>
        </x-card>

        <div class="mt-5 flex items-center gap-3">
            <button class="rounded-md bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">Update Record</button>
            <a href="{{ route('workers.show', $worker) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</x-app-layout>
