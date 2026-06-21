<x-app-layout>
    <x-slot name="title">Attendance Register</x-slot>
    <x-slot name="header">Attendance Register (مہینہ رجسٹر)</x-slot>

    @php
        // Split existing into this-project (editable) vs other-project (locked).
        $gridInit = []; $lockedInit = [];
        foreach ($existing as $wid => $byDay) {
            foreach ($byDay as $day => $info) {
                if ((int) $info['project_id'] === (int) $projectId || ($info['project_id'] === null && $projectId === null)) {
                    $gridInit[$wid][$day] = rtrim(rtrim((string) $info['days'], '0'), '.') === '0.5' ? '0.5' : '1';
                } else {
                    $lockedInit[$wid][$day] = strtoupper(mb_substr($info['project'] ?? 'P', 0, 1));
                }
            }
        }
    @endphp

    <div x-data="{
            grid: {{ \Illuminate\Support\Js::from($gridInit) }},
            locked: {{ \Illuminate\Support\Js::from($lockedInit) }},
            cycle(w,d){
                if (this.locked[w] && this.locked[w][d]) return;
                let cur = (this.grid[w] && this.grid[w][d]) || '';
                let next = cur === '' ? '1' : (cur === '1' ? '0.5' : '');
                if (!this.grid[w]) this.grid[w] = {};
                this.grid[w][d] = next;
            },
            val(w,d){ return (this.grid[w] && this.grid[w][d]) || ''; },
            workerDays(w){ let s=0; if(this.grid[w]) Object.values(this.grid[w]).forEach(v=>s+=parseFloat(v)||0); return s; }
         }">

        {{-- Controls --}}
        <form method="GET" action="{{ route('attendance.register') }}" class="mb-4 flex flex-wrap items-center gap-3">
            <a href="{{ route('attendance.register', ['month'=>$prevMonth,'project_id'=>$projectId]) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium ring-1 ring-gray-300 hover:bg-gray-50">‹ {{ \Carbon\Carbon::parse($prevMonth.'-01')->format('M y') }}</a>
            <span class="text-lg font-bold text-gray-900">{{ $monthLabel }}</span>
            <a href="{{ route('attendance.register', ['month'=>$nextMonth,'project_id'=>$projectId]) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium ring-1 ring-gray-300 hover:bg-gray-50">{{ \Carbon\Carbon::parse($nextMonth.'-01')->format('M y') }} ›</a>
            <input type="hidden" name="month" value="{{ $month }}">
            <select name="project_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">— no project (بغیر پروجیکٹ) —</option>
                @foreach($projects as $p)<option value="{{ $p->id }}" @selected((int)$projectId===$p->id)>{{ $p->name }}</option>@endforeach
            </select>
            <span class="flex items-center gap-3 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-emerald-500"></span> Present</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-amber-400"></span> Half</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-gray-200"></span> Off</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-indigo-200"></span> Other project</span>
            </span>
        </form>

        <p class="mb-3 text-xs text-gray-400">Cell pe click karo: khaali → P (present) → H (half) → khaali. Doosre project ke din locked hain.</p>

        <form method="POST" action="{{ route('attendance.register.save') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="project_id" value="{{ $projectId }}">
            <input type="hidden" name="cells" :value="JSON.stringify(grid)">

            <x-card class="!p-0">
                <div class="overflow-x-auto">
                    <table class="text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500">
                                <th class="sticky left-0 z-10 bg-gray-50 px-3 py-2 text-left">Worker (مزدور)</th>
                                @foreach($days as $d)
                                    <th class="w-9 px-0 py-2 text-center {{ $d['fri'] ? 'text-amber-600' : '' }}">
                                        <div>{{ $d['n'] }}</div><div class="text-[10px] opacity-60">{{ $d['wd'] }}</div>
                                    </th>
                                @endforeach
                                <th class="px-3 py-2 text-right">Days</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($workers as $w)
                                <tr>
                                    <td class="sticky left-0 z-10 bg-white px-3 py-2 font-medium text-gray-700 whitespace-nowrap">{{ $w->name }}<div class="text-[10px] text-gray-400">{{ ucfirst($w->role) }}</div></td>
                                    @foreach($days as $d)
                                        @php $dn = $d['n']; @endphp
                                        <td class="p-0.5 text-center">
                                            <template x-if="locked[{{ $w->id }}] && locked[{{ $w->id }}][{{ $dn }}]">
                                                <div class="mx-auto flex h-8 w-8 items-center justify-center rounded bg-indigo-100 text-[10px] font-bold text-indigo-700" x-text="locked[{{ $w->id }}][{{ $dn }}]" title="doosre project ki haazri"></div>
                                            </template>
                                            <template x-if="!(locked[{{ $w->id }}] && locked[{{ $w->id }}][{{ $dn }}])">
                                                <button type="button" @click="cycle({{ $w->id }},{{ $dn }})"
                                                    class="mx-auto flex h-8 w-8 items-center justify-center rounded text-xs font-bold"
                                                    :class="val({{ $w->id }},{{ $dn }})==='1' ? 'bg-emerald-500 text-white' : (val({{ $w->id }},{{ $dn }})==='0.5' ? 'bg-amber-400 text-white' : 'bg-gray-100 text-gray-300 hover:bg-gray-200')"
                                                    x-text="val({{ $w->id }},{{ $dn }})==='1' ? 'P' : (val({{ $w->id }},{{ $dn }})==='0.5' ? 'H' : '·')"></button>
                                            </template>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700" x-text="workerDays({{ $w->id }})"></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($days)+2 }}" class="px-4 py-8 text-center text-gray-400">Koi worker nahi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($workers->count())
                    <div class="border-t border-gray-100 p-4">
                        <button class="rounded-md bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save Register (رجسٹر محفوظ کریں)</button>
                        <a href="{{ route('attendance') }}" class="ml-3 text-sm font-medium text-gray-500 hover:text-gray-700">← Quick Mark</a>
                    </div>
                @endif
            </x-card>
        </form>
    </div>
</x-app-layout>
