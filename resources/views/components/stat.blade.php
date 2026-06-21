@props(['label', 'value', 'color' => 'text-gray-900', 'sub' => null])

<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
    <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
    <div class="mt-2 text-2xl font-bold {{ $color }}">{{ $value }}</div>
    @if ($sub)<div class="mt-1 text-xs text-gray-400">{{ $sub }}</div>@endif
</div>
