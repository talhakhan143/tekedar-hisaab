@props(['title' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white shadow-sm ring-1 ring-gray-200']) }}>
    @if ($title || $actions)
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <h2 class="font-semibold text-gray-900">{{ $title }}</h2>
            @if ($actions)<div>{{ $actions }}</div>@endif
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</div>
