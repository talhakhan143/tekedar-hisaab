<x-app-layout>
    <x-slot name="title">Edit Purchase</x-slot>
    <x-slot name="header">Edit Purchase · {{ $purchase->item_name }}</x-slot>
    <form method="POST" action="{{ route('materials.update', $purchase) }}" class="max-w-3xl">
        @csrf @method('PUT')
        @include('materials._form', ['submit' => 'Update Purchase'])
    </form>
    <p class="mt-3 max-w-3xl text-xs text-gray-400">Tip: project change karoge to reassignment audit log me record ho jayega.</p>
</x-app-layout>
