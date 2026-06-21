<x-app-layout>
    <x-slot name="title">{{ $module }}</x-slot>
    <x-slot name="header">{{ $module }}</x-slot>

    <div class="flex min-h-[50vh] flex-col items-center justify-center rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-200">
        <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-500">🚧</div>
        <h2 class="text-xl font-semibold text-gray-900">{{ $module }}</h2>
        <p class="mt-2 max-w-md text-sm text-gray-500">
            Ye module {{ $step ?? 'agle steps' }} me banega. Abhi sirf shell ready hai —
            navigation, layout aur money helpers chal rahe hain.
        </p>
    </div>
</x-app-layout>
