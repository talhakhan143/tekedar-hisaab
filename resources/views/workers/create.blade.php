<x-app-layout>
    <x-slot name="title">Naya Mazdoor</x-slot>
    <x-slot name="header">Naya Mazdoor (نیا مزدور)</x-slot>

    {{--
        Mazdoor sirf yahin se banta hai, bilkul vendor/driver ki tarah. Project
        page par sirf chuna jata hai. Pehle project page par bhi naam likhne ka
        box tha, jis se ek hi banda do dafa ban sakta tha (talha aur Talha) aur
        us ka hisaab do ID me bat jata tha.
    --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ $backProject ? route('projects.show', ['project' => $backProject, 'tab' => 'attendance']) : route('workers.index') }}"
           class="text-sm font-medium text-emerald-700 hover:underline">
            ← {{ $backProject ? $backProject->name : 'Workers' }}
        </a>
        @if($backProject)
            <span class="rounded-md bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800 ring-1 ring-emerald-100">
                Banne ke baad seedha wapis <span class="font-semibold">{{ $backProject->name }}</span> par aa jaoge, aur wahan "Mazdoor chuno" me mil jayega.
            </span>
        @endif
    </div>

    <form method="POST" action="{{ route('workers.store') }}" class="max-w-3xl">
        @csrf
        @if($backProject)
            {{-- Sirf id bhejte hain, poora URL nahi. --}}
            <input type="hidden" name="project" value="{{ $backProject->id }}">
        @endif
        @include('workers._form', ['submit' => 'Create Worker'])
    </form>
</x-app-layout>
