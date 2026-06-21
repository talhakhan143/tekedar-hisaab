<x-app-layout>
    <x-slot name="title">Edit Project</x-slot>
    <x-slot name="header">Edit · {{ $project->name }}</x-slot>

    <form method="POST" action="{{ route('projects.update', $project) }}" class="max-w-5xl">
        @csrf @method('PUT')
        @include('projects._form', ['submit' => 'Update Project'])
    </form>

    <form method="POST" action="{{ route('projects.destroy', $project) }}" class="mt-6 max-w-5xl"
          onsubmit="return confirm('Pakka delete? (soft delete — audit me rahega)')">
        @csrf @method('DELETE')
        <button class="text-sm font-medium text-red-600 hover:text-red-700">Delete Project</button>
    </form>
</x-app-layout>
