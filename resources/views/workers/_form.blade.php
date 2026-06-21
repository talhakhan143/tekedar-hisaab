<x-card title="Worker Details">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $worker->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $worker->phone)" />
        </div>
        <div>
            <x-input-label for="role" value="Role *" />
            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @foreach(['mistri','mazdoor','electrician','plumber','painter','foreman','other'] as $r)
                    <option value="{{ $r }}" @selected(old('role',$worker->role)===$r)>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="wage_type" value="Wage Type *" />
            <select id="wage_type" name="wage_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="daily" @selected(old('wage_type',$worker->wage_type)==='daily')>Daily</option>
                <option value="monthly" @selected(old('wage_type',$worker->wage_type)==='monthly')>Monthly</option>
                <option value="contract_piece" @selected(old('wage_type',$worker->wage_type)==='contract_piece')>Contract / Piece</option>
            </select>
        </div>
        <x-money-input name="default_wage" label="Default Wage (per day / month / piece) *" required
                       :value="old('default_wage', $worker->default_wage_paisa ? \App\Support\Money::toRupees($worker->default_wage_paisa) : '')" />
        <div class="sm:col-span-2">
            <x-input-label for="notes" value="Notes" />
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes', $worker->notes) }}</textarea>
        </div>
    </div>
</x-card>
<div class="mt-5 flex items-center gap-3">
    <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ $submit ?? 'Save' }}</button>
    <a href="{{ route('workers.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
</div>
