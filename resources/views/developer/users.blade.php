<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Developer · User Management') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-4 text-sm text-gray-600">
                Change any account's email and password directly. No current-password confirmation, no email re-verification. This page is only reachable by the developer account.
            </div>

            @foreach ($users as $u)
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="font-medium text-gray-900">{{ $u->name }}</div>
                            <div class="text-xs text-gray-500">ID #{{ $u->id }} @if ($u->is_developer) · developer @endif</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('developer.users.update', $u) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @csrf
                        @method('put')

                        <div>
                            <x-input-label :for="'name_'.$u->id" value="Username" />
                            <x-text-input :id="'name_'.$u->id" name="name" type="text" class="mt-1 block w-full"
                                          :value="old('name', $u->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label :for="'email_'.$u->id" value="Email" />
                            <x-text-input :id="'email_'.$u->id" name="email" type="email" class="mt-1 block w-full"
                                          :value="old('email', $u->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label :for="'password_'.$u->id" value="New Password (leave blank to keep)" />
                            <x-text-input :id="'password_'.$u->id" name="password" type="password" class="mt-1 block w-full"
                                          autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label :for="'password_confirmation_'.$u->id" value="Confirm New Password" />
                            <x-text-input :id="'password_confirmation_'.$u->id" name="password_confirmation" type="password" class="mt-1 block w-full"
                                          autocomplete="new-password" />
                        </div>

                        <div class="sm:col-span-2 flex justify-end">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @endforeach

        </div>
    </div>
</x-app-layout>
