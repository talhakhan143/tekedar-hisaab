<x-app-layout>
    <x-slot name="title">New Vendor</x-slot>
    <x-slot name="header">New Vendor</x-slot>
    <form method="POST" action="{{ route('vendors.store') }}" class="max-w-3xl">
        @csrf
        @include('vendors._form', ['submit' => 'Create Vendor'])
    </form>
</x-app-layout>
