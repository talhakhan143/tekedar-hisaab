<x-app-layout>
    <x-slot name="title">Projects</x-slot>
    <x-slot name="header">Projects</x-slot>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name / client…"
                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            <select name="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All status</option>
                @foreach (['quoted','active','on_hold','completed','closed'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Filter</button>
        </form>
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
            + New Project
        </a>
    </div>

    <x-card class="overflow-hidden !p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Contract</th>
                        <th class="px-4 py-3 text-right">Cost so far</th>
                        <th class="px-4 py-3 text-right">Projected P/L</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        @php $p = $row['project']; @endphp
                        <tr class="hover:bg-gray-50 {{ $row['isLoss'] ? 'bg-red-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('projects.show', $p) }}" class="font-medium text-emerald-700 hover:underline">{{ $p->name }}</a>
                                <div class="text-xs text-gray-500">{{ $p->client_name }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->contractTypeLabel() }}</td>
                            <td class="px-4 py-3 text-right font-medium">@money($p->contract_value_paisa)</td>
                            <td class="px-4 py-3 text-right text-gray-600">@money($row['cost'])</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $row['projected'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                @money($row['projected'])
                                @if($row['isLoss'])<span class="ml-1">⚠️</span>@endif
                            </td>
                            <td class="px-4 py-3"><x-badge :color="$p->statusColor()">{{ ucfirst(str_replace('_',' ',$p->status)) }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('projects.edit', $p) }}" class="text-gray-400 hover:text-gray-700">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Koi project nahi. "New Project" se start karo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>
