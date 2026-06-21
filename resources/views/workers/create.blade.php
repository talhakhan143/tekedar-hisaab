<x-app-layout>
    <x-slot name="title">New Worker</x-slot>
    <x-slot name="header">New Worker</x-slot>
    <form method="POST" action="{{ route('workers.store') }}" class="max-w-3xl">
        @csrf
        @include('workers._form', ['submit' => 'Create Worker'])
    </form>
</x-app-layout>
