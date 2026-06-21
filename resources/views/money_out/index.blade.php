<x-app-layout>
    <x-slot name="title">Money Out</x-slot>
    <x-slot name="header">Money Out</x-slot>

    {{-- Summary --}}
    <div class="mb-5 grid grid-cols-2 gap-4 xl:grid-cols-5">
        <x-stat label="Material" :value="\App\Support\Money::short($materialSpent)" color="text-amber-600" />
        <x-stat label="Labour Paid" :value="\App\Support\Money::short($labourPaid)" color="text-indigo-600" />
        <x-stat label="Other Expenses" :value="\App\Support\Money::short($otherTotal)" color="text-sky-600" />
        <x-stat label="General Overheads" :value="\App\Support\Money::short($overheadTotal)" color="text-gray-700" />
        <x-stat label="Grand Total Out" :value="\App\Support\Money::short($grandTotal)" color="text-red-600" />
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('materials.create') }}" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">+ Material Purchase</a>
        <a href="{{ route('materials.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">All Materials</a>
        <a href="{{ route('workers.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Wages (Workers)</a>
    </div>

    {{-- Other expenses --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <x-card title="Add Other Expense">
                <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
                    @csrf
                    <select name="category" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach($categories as $c)<option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>@endforeach
                    </select>
                    <select name="project_id" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— general / office (no project) —</option>
                        @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                    <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <input type="text" name="description" placeholder="Description" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <x-money-input name="amount" label="Amount" required />
                    <input type="text" name="paid_to" placeholder="Paid to" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add Expense</button>
                </form>
            </x-card>
        </div>
        <div class="lg:col-span-2">
            <x-card title="Other Expenses" class="!p-0">
                <div class="max-h-96 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Description</th><th class="px-4 py-3">Project</th><th class="px-4 py-3 text-right">Amount</th><th></th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($expenses as $e)
                                <tr>
                                    <td class="px-4 py-2.5">{{ $e->date->format('d-m-Y') }}</td>
                                    <td class="px-4 py-2.5 capitalize">{{ str_replace('_',' ',$e->category) }}</td>
                                    <td class="px-4 py-2.5 text-gray-600">{{ $e->description }}</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $e->project->name ?? 'General' }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium">@money($e->amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-right"><form method="POST" action="{{ route('expenses.destroy', $e) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                                </tr>
                            @empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Koi expense nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>

    {{-- General overheads --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <x-card title="Add General Overhead (monthly)">
                <form method="POST" action="{{ route('overheads.store') }}" class="space-y-3">
                    @csrf
                    <input type="month" name="month" value="{{ now()->format('Y-m') }}" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <input type="text" name="category" placeholder="Category (office_rent, salary_draw…)" required class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <x-money-input name="amount" label="Amount" required />
                    <p class="text-xs text-gray-400">Overheads reduce OVERALL profit (not per-project). Settings me pro-rata allocation toggle hai.</p>
                    <button class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add Overhead</button>
                </form>
            </x-card>
        </div>
        <div class="lg:col-span-2">
            <x-card title="General Overheads" class="!p-0">
                <div class="max-h-96 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="sticky top-0 bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Month</th><th class="px-4 py-3">Category</th><th class="px-4 py-3 text-right">Amount</th><th></th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($overheads as $o)
                                <tr>
                                    <td class="px-4 py-2.5">{{ $o->month }}</td>
                                    <td class="px-4 py-2.5 capitalize">{{ str_replace('_',' ',$o->category) }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium">@money($o->amount_paisa)</td>
                                    <td class="px-4 py-2.5 text-right"><form method="POST" action="{{ route('overheads.destroy', $o) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-600">✕</button></form></td>
                                </tr>
                            @empty<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Koi overhead nahi.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
