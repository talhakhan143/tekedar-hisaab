<x-card title="Worker Details">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="name" value="Name *" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $worker->name)" required />
            {{-- Naam par rok ka message yahi dikhta hai, is liye ye line hatani nahi. --}}
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
            {{--
                Isi naam ka banda pehle se ho to app rok deti hai, warna aik hi
                aadmi ke do khaate ban jate hain aur hisaab bant jata hai. Magar
                Pakistan me ek hi naam ke do bande hona aam baat hai, is liye
                hamesha ke liye mana bhi nahi kar sakte. Ye tick sirf us soorat
                me lagta hai jab app naam par rok de.
            --}}
            <label class="mt-2 flex items-start gap-2 text-[11px] leading-snug text-gray-500">
                <input type="hidden" name="allow_duplicate" value="0">
                <input type="checkbox" name="allow_duplicate" value="1" @checked(old('allow_duplicate'))
                       class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                <span>
                    <span class="font-medium text-gray-600">Alag banda hai (isi naam ka doosra mazdoor)</span>
                    <span class="block text-gray-400">Sirf tab lagao jab app kahe ke ye naam pehle se mojood hai.</span>
                </span>
            </label>
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $worker->phone)" />
        </div>
        <div>
            <x-input-label for="role" value="Role *" />
            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                @php
                    // Settings wali list, aur sath me bande ka mojooda role bhi,
                    // warna Settings se role hatne par save karte hi us ka role
                    // chupke se badal jata.
                    $roleOptions = \App\Models\Setting::workerRoles();
                    $currentRole = old('role', $worker->role);
                    if ($currentRole && ! in_array($currentRole, $roleOptions, true)) {
                        $roleOptions[] = $currentRole;
                    }
                @endphp
                @foreach($roleOptions as $r)
                    <option value="{{ $r }}" @selected($currentRole === $r)>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
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
