<x-app-layout>
    <x-slot name="title">Workers</x-slot>
    <x-slot name="header">Workers (مزدور)</x-slot>

    @php
        // Filter ki values WorkerController::index() se aati hain. Yahan default
        // rakhe hain taake purana cache ya koi doosra raasta is page ko khole to
        // bhi list khulti rahe, khali screen na aaye.
        $activeQ    = $filters['q'] ?? '';
        $activeRole = $filters['role'] ?? '';
        $roleOptions = $roles ?? \App\Models\Setting::workerRoles();
        $filterOn   = $activeQ !== '' || $activeRole !== '';
    @endphp

    {{--
        Malik ko ye saaf dikhna chahiye: mazdoor ki list poori company ki ek hi
        hai, project ke sath bandhi hui nahi. Ye sawal aaya tha ke "ek project me
        add kiya banda doosre project me kahan se aayega", jawab page par likha ho.
    --}}
    <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
        Ye list saare projects me milti hai. Ek bar add karo, har project me aayega.
        <span class="mt-0.5 block text-xs text-emerald-700">Har mazdoor ka apna ID hota hai, isi liye har project ka hisaab ek hi khaate me jurta hai. (یہ لسٹ ہر پروجیکٹ میں ملتی ہے)</span>
    </div>

    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <x-stat :label="$filterOn ? 'Is Filter ke Mazdoor (فلٹر والے)' : 'Total Mazdoor (کل مزدور)'"
                :value="number_format($rows->total())"
                :sub="$filterOn ? 'Filter lagi hui hai, poori list dekhne ke liye Clear karo.' : 'Ek hi list, saare projects ke liye.'" />
        <x-stat label="Total Worker Advances (کل پیشگی)" :value="\App\Support\Money::format($totalAdvances)" color="text-amber-600" />
    </div>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('workers.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ $activeQ }}" placeholder="Naam se dhoondo…"
                   class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:w-52">
            <select name="role" class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:flex-none">
                <option value="">Sab kaam (سب)</option>
                @foreach ($roleOptions as $r)
                    <option value="{{ $r }}" @selected($activeRole === $r)>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                @endforeach
            </select>
            <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>
            <a href="{{ route('workers.index') }}" class="px-1 py-2 text-sm font-medium text-gray-500 underline hover:text-gray-700">Clear</a>
        </form>
        <a href="{{ route('workers.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ New Worker (نیا مزدور)</a>
    </div>

    <x-card class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Name (نام)</th>
                        <th class="px-4 py-3">Role (کام)</th>
                        <th class="px-4 py-3">Wage (دیہاڑی)</th>
                        <th class="px-4 py-3 text-right">Earned (کمایا)</th>
                        <th class="px-4 py-3 text-right">Advances (پیشگی)</th>
                        <th class="px-4 py-3 text-right">Payable Now (قابلِ ادائیگی)</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php $w = $row['worker']; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2.5"><a href="{{ route('workers.show', $w) }}" class="font-medium text-emerald-700 hover:underline">{{ $w->name }}</a> <span class="text-xs text-gray-400">#{{ $w->id }}</span><div class="text-xs text-gray-400">{{ $w->phone }}</div></td>
                            <td class="px-4 py-2.5 capitalize">{{ $w->role }}</td>
                            <td class="px-4 py-2.5 text-gray-500">@money($w->default_wage_paisa) / {{ str_replace('contract_piece','piece',$w->wage_type) }}</td>
                            <td class="px-4 py-2.5 text-right">@money($row['earned'])</td>
                            <td class="px-4 py-2.5 text-right {{ $row['advances'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">@money($row['advances'])</td>
                            <td class="px-4 py-2.5 text-right font-semibold {{ $row['payable'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">@money($row['payable'])</td>
                            <td class="px-4 py-2.5 text-right"><a href="{{ route('workers.edit', $w) }}" class="text-gray-400 hover:text-gray-700">Edit</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                @if($filterOn)
                                    Is filter se koi mazdoor nahi mila.
                                    <a href="{{ route('workers.index') }}" class="font-medium text-emerald-700 underline hover:text-emerald-800">Clear karo</a>
                                    aur poori list dekho.
                                @else
                                    Koi mazdoor nahi. "New Worker" se add karo, phir wo har project me aayega.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- appends() se page badalne par filter lagi rehti hai. --}}
        @if($rows->hasPages())<div class="border-t border-gray-100 p-3">{{ $rows->appends(request()->query())->links() }}</div>@endif
    </x-card>
</x-app-layout>
