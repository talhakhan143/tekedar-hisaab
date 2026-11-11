<x-app-layout>
    <x-slot name="title">Edit Expense</x-slot>
    <x-slot name="header">Money Out · Kharcha Durust Karo (دیگر خرچ)</x-slot>

    @php
        $amountR = old('amount', \App\Support\Money::toRupees($expense->amount_paisa));
        $pickedProject = (string) old('project_id', $expense->project_id ?? '');
    @endphp

    <form method="POST" action="{{ route('expenses.update', $expense) }}" class="max-w-2xl">
        @csrf @method('PUT')
        <x-card title="Expense Details (خرچ کی تفصیل)">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="category" value="Category *" />
                    <select id="category" name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach($categories as $c)
                            <option value="{{ $c }}" @selected(old('category', $expense->category) === $c)>{{ ucfirst(str_replace('_',' ',$c)) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="project_id" value="Project (optional)" />
                    <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">— general / office (no project) —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected($pickedProject === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="date" value="Date *" />
                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', optional($expense->date)->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-1" />
                </div>

                <x-money-input name="amount" label="Amount (رقم)" :value="$amountR" required />

                <div class="sm:col-span-2">
                    <x-input-label for="description" value="Description" />
                    <x-text-input id="description" name="description" class="mt-1 block w-full" :value="old('description', $expense->description)" />
                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="paid_to" value="Paid to (kis ko diya)" />
                    <x-text-input id="paid_to" name="paid_to" class="mt-1 block w-full" :value="old('paid_to', $expense->paid_to)" />
                    <x-input-error :messages="$errors->get('paid_to')" class="mt-1" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Notes" />
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $expense->notes) }}</textarea>
                </div>
            </div>
        </x-card>

        <div class="mt-5 flex items-center gap-3">
            <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Update Expense</button>
            <a href="{{ route('money-out') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</x-app-layout>
