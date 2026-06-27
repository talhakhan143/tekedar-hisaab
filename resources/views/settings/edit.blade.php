<x-app-layout>
    <x-slot name="title">Settings</x-slot>
    <x-slot name="header">Settings (ترتیبات)</x-slot>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf @method('PUT')

        <x-card title="Company (کمپنی)">
            <div class="space-y-5">
                <div>
                    <x-input-label for="company_name" value="Company Name *" />
                    <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" :value="old('company_name', $settings['company_name'])" required />
                    <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="company_address" value="Address (invoices/vouchers پر)" />
                    <x-text-input id="company_address" name="company_address" class="mt-1 block w-full" :value="old('company_address', $settings['company_address'])" placeholder="Office address" />
                    <x-input-error :messages="$errors->get('company_address')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="company_phone" value="Phone (invoices/vouchers پر)" />
                    <x-text-input id="company_phone" name="company_phone" class="mt-1 block w-full" :value="old('company_phone', $settings['company_phone'])" placeholder="03xx-xxxxxxx" />
                    <x-input-error :messages="$errors->get('company_phone')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="logo" value="Logo (PNG/JPG, max 2MB)" />
                    @if(!empty($settings['company_logo']))
                        <img src="{{ $settings['company_logo'] }}" alt="logo" class="my-2 h-12">
                    @endif
                    <input type="file" id="logo" name="logo" accept="image/*" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700">
                    <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                </div>
            </div>
        </x-card>

        <x-card title="Defaults (طے شدہ اقدار)">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="default_retention" value="Default Retention % *" />
                    <x-text-input id="default_retention" name="default_retention" type="number" step="0.01" class="mt-1 block w-full" :value="old('default_retention', $settings['default_retention'])" required />
                </div>
                <div>
                    <x-input-label for="default_wastage" value="Default Wastage % *" />
                    <x-text-input id="default_wastage" name="default_wastage" type="number" step="0.01" class="mt-1 block w-full" :value="old('default_wastage', $settings['default_wastage'])" required />
                </div>
            </div>
        </x-card>

        <x-card title="Overhead Allocation">
            <label class="flex items-start gap-3">
                <input type="hidden" name="allocate_overheads" value="0">
                <input type="checkbox" name="allocate_overheads" value="1" @checked(($settings['allocate_overheads'] ?? '0')==='1')
                       class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-sm text-gray-700">
                    <span class="font-medium">Allocate general overheads pro-rata across active projects</span><br>
                    <span class="text-gray-500">ON: monthly overheads distribute into each active project's cost by contract value (affects per-project profit). OFF (default): overheads only reduce overall profit.</span>
                </span>
            </label>
        </x-card>

        <x-card title="Worker Roles (مزدور کی قسمیں)">
            <div>
                <x-input-label for="worker_roles" value="Roles — comma se alag karo" />
                <textarea id="worker_roles" name="worker_roles" rows="2"
                          class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                          placeholder="mistri, mazdoor, electrician, plumber, painter, foreman, other">{{ old('worker_roles', $settings['worker_roles'] ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('worker_roles')" class="mt-1" />
                <p class="mt-2 text-xs text-gray-500">Ye "Naya Mazdoor" form ke role dropdown me dikhte hain. Comma laga ke jitne chaaho add/edit/remove karo (jaise: <span class="font-medium">welder, tile mistri, helper</span>).</p>
            </div>
        </x-card>

        <x-card title="Expense Categories">
            <p class="text-sm text-gray-500">Money-out categories (material, labour, transport, equipment, subcontractor, utility, overhead, misc) aur other-expense categories code-level enums hain — naye categories add karne ke liye migration update karni hogi. General overhead categories free-text hain (Money Out page se koi bhi naam de sakte ho).</p>
        </x-card>

        <button class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save Settings</button>
    </form>

    {{-- Danger Zone — hard reset of all business data (developer + owner only) --}}
    <div class="mt-8 rounded-lg border-2 border-red-300 bg-red-50 p-6" x-data="{ confirm: '' }">
        <h3 class="text-lg font-bold text-red-700">⚠️ Danger Zone — Hard Reset</h3>
        <p class="mt-1 text-sm text-red-700">
            Ye sara business data <span class="font-bold">permanently</span> mita dega — projects, vendors, mazdoor,
            attendance, payments, materials, expenses, estimates — sab. <span class="font-bold">Users aur Settings
            (company name, logo) safe rahenge.</span> Test ke baad fresh start ke liye use karo. Wapas nahi aa sakta.
        </p>

        <form method="POST" action="{{ route('hard-reset') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
              onsubmit="return confirm('PAKKA? Sara data mit jayega aur wapas nahi aayega.')">
            @csrf
            <div>
                <label for="reset_confirm" class="block text-xs font-medium text-red-700">Confirm karne ke liye "RESET" likho</label>
                <input id="reset_confirm" name="confirm" type="text" autocomplete="off" placeholder="RESET"
                       x-model="confirm"
                       class="mt-1 block w-48 rounded-md border-red-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
                <x-input-error :messages="$errors->get('confirm')" class="mt-1" />
            </div>
            <button type="submit" :disabled="confirm !== 'RESET'"
                    class="rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40">
                Hard Reset — Sab Mita Do
            </button>
        </form>
    </div>
</x-app-layout>
