<x-app-layout>
    <x-slot name="title">Edit Worker</x-slot>
    <x-slot name="header">Edit · {{ $worker->name }}</x-slot>
    <form method="POST" action="{{ route('workers.update', $worker) }}" class="max-w-3xl">
        @csrf @method('PUT')
        @include('workers._form', ['submit' => 'Update Worker'])
    </form>
    <form method="POST" action="{{ route('workers.destroy', $worker) }}" class="mt-6 max-w-3xl" onsubmit="return confirm('Delete worker?')">
        @csrf @method('DELETE')
        <button class="text-sm font-medium text-red-600 hover:text-red-700">Delete Worker</button>
    </form>
</x-app-layout>
