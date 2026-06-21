@props(['color' => 'gray'])

@php
    $map = [
        'gray'    => 'bg-gray-100 text-gray-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'sky'     => 'bg-sky-100 text-sky-700',
        'amber'   => 'bg-amber-100 text-amber-700',
        'indigo'  => 'bg-indigo-100 text-indigo-700',
        'red'     => 'bg-red-100 text-red-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.($map[$color] ?? $map['gray'])]) }}>
    {{ $slot }}
</span>
