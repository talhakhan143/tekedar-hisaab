<x-app-layout>
    <x-slot name="title">Mazdoor ka hisaab</x-slot>
    <x-slot name="header">Mazdoor ka hisaab, saare projects (مزدور کا حساب)</x-slot>

    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('reports') }}" class="text-sm font-medium text-emerald-700 hover:underline">← Reports</a>
        <span class="flex-1"></span>
        <a href="{{ route('reports.labour', array_merge($filters, ['export' => 'csv'])) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Export CSV</a>
        <a href="{{ route('reports.labour', array_merge($filters, ['export' => 'pdf'])) }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Export PDF</a>
    </div>

    {{-- Filter: banda chuno, kaam chuno, aur ya saare projects ya ek project --}}
    <x-card class="mb-5">
        <form method="GET" action="{{ route('reports.labour') }}" x-data="{ all: {{ $allProjects ? 'true' : 'false' }} }" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Banda (کون)</label>
                <select name="worker" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="all">Saare mazdoor (سب)</option>
                    @foreach($allWorkers as $w)
                        <option value="{{ $w->id }}" @selected($oneWorker === (int) $w->id)>{{ $w->name }} · {{ ucfirst($w->role) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Kaam (کام)</label>
                <select name="role" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="all">Har kaam</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}" @selected($roleParam === $r)>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-400">Sirf "saare mazdoor" ke saath chalta hai.</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Project (پروجیکٹ)</label>
                <select name="project" x-bind:disabled="all" x-bind:class="all ? 'opacity-50' : ''" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @forelse($projectNames as $pid => $pname)
                        <option value="{{ $pid }}" @selected($projectId === (int) $pid)>{{ $pname }}</option>
                    @empty
                        <option value="">koi project nahi</option>
                    @endforelse
                </select>
            </div>

            <div class="flex flex-col justify-end gap-3">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    {{-- hidden 0 pehle, checkbox 1 baad me: tick hatao to bhi server ko pata chalta hai --}}
                    <input type="hidden" name="all_projects" value="0">
                    <input type="checkbox" name="all_projects" value="1" x-model="all" @checked($allProjects) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    Saare projects (تمام پروجیکٹ)
                </label>
                <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Hisaab dekho</button>
            </div>
        </form>
    </x-card>

    <div class="mb-2 text-sm text-gray-500">{{ $scopeLabel }}</div>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Banta tha (کل بنتا تھا)" :value="\App\Support\Money::format($grand['earned'])" />
        <x-stat label="Diya (دیا)" :value="\App\Support\Money::format($grand['paid'])" color="text-emerald-600" />
        <x-stat label="Peshgi (پیشگی)" :value="\App\Support\Money::format($grand['advnet'])" :color="$grand['advnet'] > 0 ? 'text-amber-600' : 'text-gray-400'" />
        <x-stat
            label="Baqi (باقی)"
            :value="\App\Support\Money::format($grand['baqi'])"
            :color="$grand['baqi'] < 0 ? 'text-amber-600' : 'text-red-600'"
            :sub="$grand['dena'] && $grand['advance'] ? 'Dena ' . \App\Support\Money::short($grand['dena']) . ' · Advance ' . \App\Support\Money::short($grand['advance']) : null" />
    </div>

    @forelse($report as $r)
        <div class="mb-5">
            <x-card class="!p-0">
                <x-slot name="title">
                    <a href="{{ route('workers.show', $r['worker']) }}" class="text-emerald-700 hover:underline">{{ $r['worker']->name }}</a>
                    <span class="ml-1 text-xs font-normal text-gray-400">#{{ $r['worker']->id }} · {{ ucfirst($r['worker']->role) }}</span>
                </x-slot>
                <x-slot name="actions">
                    @if($r['total']['baqi'] > 0)
                        <x-badge color="red">Baqi @money($r['total']['baqi'])</x-badge>
                    @elseif($r['total']['baqi'] < 0)
                        <x-badge color="amber">Advance @money(-$r['total']['baqi'])</x-badge>
                    @else
                        <x-badge color="emerald">Hisaab barabar</x-badge>
                    @endif
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Project (پروجیکٹ)</th>
                                <th class="px-4 py-3 text-right">Din (دن)</th>
                                <th class="px-4 py-3 text-right">Banta tha (بنتا تھا)</th>
                                <th class="px-4 py-3 text-right">Diya (دیا)</th>
                                <th class="px-4 py-3 text-right">Peshgi (پیشگی)</th>
                                <th class="px-4 py-3 text-right">Baqi (باقی)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($r['lines'] as $l)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 font-medium text-gray-700">
                                        @if($l['project_id'] === 0)
                                            Bina project <span class="text-xs font-normal text-gray-400">(بغیر پروجیکٹ)</span>
                                        @else
                                            {{ $l['project'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-gray-500">{{ rtrim(rtrim(number_format($l['days'], 1), '0'), '.') }}</td>
                                    <td class="px-4 py-2.5 text-right">@money($l['earned'])</td>
                                    <td class="px-4 py-2.5 text-right text-emerald-600">@money($l['paid'])</td>
                                    <td class="px-4 py-2.5 text-right {{ $l['advnet'] ? 'text-amber-600' : 'text-gray-400' }}">@money($l['advnet'])</td>
                                    <td class="px-4 py-2.5 text-right font-semibold">
                                        @if($l['baqi'] > 0)
                                            <span class="text-red-600">@money($l['baqi'])</span><span class="ml-1 text-xs font-normal text-gray-400">dena</span>
                                        @elseif($l['baqi'] < 0)
                                            <span class="text-amber-600">@money(-$l['baqi'])</span><span class="ml-1 text-xs font-normal text-gray-400">advance</span>
                                        @else
                                            <span class="text-gray-400">barabar</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Is mazdoor ka koi hisaab nahi.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($r['lines']))
                            <tfoot class="bg-gray-100 font-bold">
                                <tr>
                                    <td class="px-4 py-3">TOTAL</td>
                                    <td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format($r['total']['days'], 1), '0'), '.') }}</td>
                                    <td class="px-4 py-3 text-right">@money($r['total']['earned'])</td>
                                    <td class="px-4 py-3 text-right text-emerald-600">@money($r['total']['paid'])</td>
                                    <td class="px-4 py-3 text-right {{ $r['total']['advnet'] ? 'text-amber-600' : 'text-gray-400' }}">@money($r['total']['advnet'])</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($r['total']['baqi'] > 0)
                                            <span class="text-red-600">@money($r['total']['baqi'])</span><span class="ml-1 text-xs font-normal text-gray-400">dena</span>
                                        @elseif($r['total']['baqi'] < 0)
                                            <span class="text-amber-600">@money(-$r['total']['baqi'])</span><span class="ml-1 text-xs font-normal text-gray-400">advance</span>
                                        @else
                                            <span class="text-gray-400">barabar</span>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-card>
        </div>
    @empty
        <x-card>
            <p class="py-6 text-center text-gray-400">Koi mazdoor nahi mila. Filter badal kar dekho.</p>
        </x-card>
    @endforelse

    @if(count($report) > 1)
        <x-card title="Sab mila kar (کل میزان)" class="!p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ count($report) }} bande</th>
                            <th class="px-4 py-3 text-right">Din</th>
                            <th class="px-4 py-3 text-right">Banta tha</th>
                            <th class="px-4 py-3 text-right">Diya</th>
                            <th class="px-4 py-3 text-right">Peshgi</th>
                            <th class="px-4 py-3 text-right">Baqi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-4 py-3">GRAND TOTAL</td>
                            <td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format($grand['days'], 1), '0'), '.') }}</td>
                            <td class="px-4 py-3 text-right">@money($grand['earned'])</td>
                            <td class="px-4 py-3 text-right text-emerald-600">@money($grand['paid'])</td>
                            <td class="px-4 py-3 text-right {{ $grand['advnet'] ? 'text-amber-600' : 'text-gray-400' }}">@money($grand['advnet'])</td>
                            <td class="px-4 py-3 text-right {{ $grand['baqi'] < 0 ? 'text-amber-600' : 'text-red-600' }}">@money($grand['baqi'])</td>
                        </tr>
                        @if($grand['dena'] && $grand['advance'])
                            {{-- Do bandon ka ulta hisaab aapas me cancel nahi hota, is liye alag se --}}
                            <tr class="border-t border-gray-200 text-xs text-gray-500">
                                <td class="px-4 py-2" colspan="5">Jin se lena hai (advance) aur jin ko dena hai, alag alag</td>
                                <td class="px-4 py-2 text-right">
                                    <div><span class="font-semibold text-red-600">@money($grand['dena'])</span> dena</div>
                                    <div><span class="font-semibold text-amber-600">@money($grand['advance'])</span> advance</div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</x-app-layout>
