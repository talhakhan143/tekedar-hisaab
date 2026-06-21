<x-app-layout>
    <x-slot name="title">Attendance</x-slot>
    <x-slot name="header">Daily Attendance (روزانہ حاضری)</x-slot>

    <div x-data="{
            mode: 'single',
            rows: { @foreach($workers as $w) {{ $w->id }}: '1', @endforeach },
            setAll(v){ Object.keys(this.rows).forEach(k => this.rows[k] = v); },
            showNew: false,
         }">

        <form method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="mode" :value="mode">

            {{-- Top controls --}}
            <x-card class="mb-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Project (پروجیکٹ)</label>
                        <select name="project_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— no project (بغیر پروجیکٹ) —</option>
                            @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}{{ $p->status!=='active' ? ' ('.$p->status.')' : '' }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mode (طریقہ)</label>
                        <div class="mt-1 flex rounded-md ring-1 ring-gray-300 overflow-hidden text-sm">
                            <button type="button" @click="mode='single'" :class="mode==='single' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700'" class="flex-1 px-3 py-2 font-medium">Single day (ایک دن)</button>
                            <button type="button" @click="mode='range'" :class="mode==='range' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700'" class="flex-1 px-3 py-2 font-medium">Range / month (مہینہ)</button>
                        </div>
                    </div>

                    {{-- Single date --}}
                    <template x-if="mode==='single'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date (تاریخ)</label>
                            <input type="date" name="date" value="{{ $today }}" max="{{ $today }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </template>

                    {{-- Range --}}
                    <template x-if="mode==='range'">
                        <div class="lg:col-span-2 grid grid-cols-2 gap-3" x-data="{ from: '{{ $monthStart }}', to: '{{ $today }}' }">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">From (سے)</label>
                                <input type="date" name="from" x-model="from" max="{{ $today }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">To (تک)</label>
                                <input type="date" name="to" x-model="to" max="{{ $today }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <label class="col-span-2 flex items-center gap-2 text-xs text-gray-600">
                                <input type="hidden" name="skip_fridays" value="0">
                                <input type="checkbox" name="skip_fridays" value="1" class="rounded border-gray-300 text-emerald-600"> Friday chhod do (Friday skip)
                            </label>
                            <p class="col-span-2 text-xs text-amber-600">Range me har din ki haazri lagegi. Jo din pehle se lagi hai, skip ho jayegi.</p>
                        </div>
                    </template>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t pt-4">
                    <span class="text-sm text-gray-500">Sab pe:</span>
                    <button type="button" @click="setAll('1')" class="rounded-md bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100">All Present (سب حاضر)</button>
                    <button type="button" @click="setAll('0.5')" class="rounded-md bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-100">All Half (سب آدھا)</button>
                    <button type="button" @click="setAll('0')" class="rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-200">All Absent (سب غیرحاضر)</button>
                    <span class="flex-1"></span>
                    <button type="button" @click="showNew=!showNew" class="rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">+ New Worker (نیا مزدور)</button>
                </div>
            </x-card>

            {{-- Worker grid --}}
            <x-card title="Workers (مزدور) — Present / Half / Absent" class="!p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Worker (مزدور)</th>
                                <th class="px-4 py-3">Daily Wage (دیہاڑی)</th>
                                <th class="px-4 py-3 text-center">Status (حالت)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($workers as $w)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-700">{{ $w->name }} <span class="text-xs text-gray-400">{{ ucfirst($w->role) }}</span></td>
                                    <td class="px-4 py-3 text-gray-600">@money($w->default_wage_paisa)</td>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="present[{{ $w->id }}]" x-model="rows[{{ $w->id }}]">
                                        <div class="flex justify-center gap-1">
                                            <button type="button" @click="rows[{{ $w->id }}]='1'"   :class="rows[{{ $w->id }}]==='1'   ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Present</button>
                                            <button type="button" @click="rows[{{ $w->id }}]='0.5'" :class="rows[{{ $w->id }}]==='0.5' ? 'bg-amber-500 text-white'   : 'bg-gray-100 text-gray-600'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Half</button>
                                            <button type="button" @click="rows[{{ $w->id }}]='0'"   :class="rows[{{ $w->id }}]==='0'   ? 'bg-gray-500 text-white'    : 'bg-gray-100 text-gray-600'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Absent</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Koi worker nahi. Upar "+ New Worker" se add karo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($workers->count())
                    <div class="border-t border-gray-100 p-4">
                        <button class="rounded-md bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save Attendance (حاضری محفوظ کریں)</button>
                    </div>
                @endif
            </x-card>
        </form>

        {{-- Inline new worker --}}
        <div x-show="showNew" x-transition x-cloak class="mt-5">
            <x-card title="Quick New Worker (نیا مزدور)">
                <form method="POST" action="{{ route('attendance.quick-worker') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4 sm:items-end">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Name (نام) *</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role (کام)</label>
                        <select name="role" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach(['mistri','mazdoor','electrician','plumber','painter','foreman','other'] as $r)<option value="{{ $r }}">{{ ucfirst($r) }}</option>@endforeach
                        </select>
                    </div>
                    <x-money-input name="default_wage" label="Daily Wage (دیہاڑی) *" required />
                    <div class="sm:col-span-4">
                        <button class="rounded-md bg-gray-800 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-700">Add Worker (شامل کریں)</button>
                    </div>
                </form>
            </x-card>
        </div>

        @if($pieceWorkers->count())
            <p class="mt-4 text-xs text-gray-400">Note: piece-work ({{ $pieceWorkers->pluck('name')->join(', ') }}) yahan nahi — unki units worker page se lagao.</p>
        @endif
    </div>
</x-app-layout>
