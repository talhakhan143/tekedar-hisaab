<x-app-layout>
    <x-slot name="title">New Purchase</x-slot>
    <x-slot name="header">Money Out · New Material Purchase</x-slot>
    <form method="POST" action="{{ route('materials.store') }}" class="max-w-3xl">
        @csrf
        @include('materials._form', ['purchase' => new \App\Models\MaterialPurchase(), 'submit' => 'Save Purchase'])
    </form>
</x-app-layout>
