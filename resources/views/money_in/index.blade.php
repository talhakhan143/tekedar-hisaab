<x-app-layout>
    <x-slot name="title">Money In</x-slot>
    <x-slot name="header">Money In (آمدنی)</x-slot>

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="grid grid-cols-1 gap-4">
            <x-stat label="Total Received (کل وصولی)" :value="\App\Support\Money::format($totalReceived)" color="text-emerald-600" />
        </div>
        <a href="{{ route('client-payments.create') }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">+ New Client Payment (نئی ادائیگی)</a>
    </div>

    {{-- Client payments --}}
    <x-card title="Client Payments (کلائنٹ کی ادائیگیاں)" class="!p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3 text-right">Amount</th>
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
                            <td class="px-4 py-2.5 text-right font-medium text-emerald-600">@money($pay->net_received_paisa)</td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('vouchers.client-payment', $pay) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800">🖨 Receipt</a>
                                <form method="POST" action="{{ route('client-payments.destroy', $pay) }}" class="ml-2 inline" onsubmit="return confirm('Delete payment?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Koi payment nahi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())<div class="border-t border-gray-100 p-3">{{ $payments->links() }}</div>@endif
    </x-card>

</x-app-layout>
