<x-app-layout>
    <x-slot name="title">Edit Vendor</x-slot>
    <x-slot name="header">Edit · {{ $vendor->name }}</x-slot>
    <form method="POST" action="{{ route('vendors.update', $vendor) }}" class="max-w-3xl">
        @csrf @method('PUT')
        @include('vendors._form', ['submit' => 'Update Vendor'])
    </form>
    <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" class="mt-6 max-w-3xl" onsubmit="return confirm('Delete vendor?')">
        @csrf @method('DELETE')
        <button class="text-sm font-medium text-red-600 hover:text-red-700">Delete Vendor</button>
    </form>
</x-app-layout>
