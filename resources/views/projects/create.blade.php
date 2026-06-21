<x-app-layout>
    <x-slot name="title">New Project</x-slot>
    <x-slot name="header">New Project</x-slot>

    <form method="POST" action="{{ route('projects.store') }}" class="max-w-5xl">
        @csrf
        @include('projects._form', ['submit' => 'Create Project'])
    </form>
</x-app-layout>
