<x-app-layout>
    <x-slot name="title">Money In</x-slot>
    <x-slot name="header">Money In</x-slot>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="grid grid-cols-2 gap-4">
            <x-stat label="Total Net Received" :value="\App\Support\Money::format($totalReceived)" color="text-emerald-600" />
            <x-stat label="Retention Outstanding" :value="\App\Support\Money::format($totalRetention)" color="text-amber-600" />
        </div>
        <a href="{{ route('client-payments.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ New Client Payment</a>
    </div>

    {{-- Client payments --}}
    <x-card title="Client Payments" class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3 text-right">Gross</th>
                        <th class="px-4 py-3 text-right">Retention</th>
                        <th class="px-4 py-3 text-right">Net</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $pay)
                        <tr>
                            <td class="px-4 py-2.5">{{ $pay->date->format('d-m-Y') }}</td>
                            <td class="px-4 py-2.5">
                                <a href="{{ route('projects.show', $pay->project_id) }}" class="text-emerald-700 hover:underline">{{ $pay->project->name ?? '—' }}</a>
                                @if($pay->is_mobilization)<x-badge color="sky">Mob</x-badge>@endif
                            </td>
                            <td class="px-4 py-2.5 capitalize">{{ $pay->payment_method }}</td>
                            <td class="px-4 py-2.5 text-right">@money($pay->gross_amount_paisa)</td>
                            <td class="px-4 py-2.5 text-right text-amber-600">@money($pay->retention_held_paisa)</td>
                            <td class="px-4 py-2.5 text-right font-medium text-emerald-600">@money($pay->net_received_paisa)</td>
                            <td class="px-4 py-2.5 text-right">
                                <form method="POST" action="{{ route('client-payments.destroy', $pay) }}" onsubmit="return confirm('Delete payment?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Koi payment nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Retention releases --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <x-card title="Release Retention">
                <form method="POST" action="{{ route('retention-releases.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="rr_project" value="Project *" />
                        <select id="rr_project" name="project_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— select —</option>
                            @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="rr_date" value="Date *" />
                        <x-text-input id="rr_date" name="date" type="date" class="mt-1 block w-full" :value="now()->format('Y-m-d')" required />
                    </div>
                    <x-money-input name="amount" label="Amount Released" required />
                    <button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Record Release</button>
                </form>
            </x-card>
        </div>
        <div class="lg:col-span-2">
            <x-card title="Retention Releases" class="!p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Project</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3">Notes</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($releases as $r)
                                <tr>
                                    <td class="px-4 py-2.5">{{ $r->date->format('d-m-Y') }}</td>
                                    <td class="px-4 py-2.5">{{ $r->project->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-emerald-600">@money($r->amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $r->notes }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <form method="POST" action="{{ route('retention-releases.destroy', $r) }}" onsubmit="return confirm('Delete release?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-500 hover:text-red-700">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Koi release nahi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
