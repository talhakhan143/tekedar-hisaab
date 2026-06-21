@props(['name', 'label', 'value' => null, 'required' => false, 'help' => null, 'step' => '0.01'])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>
    <div class="relative mt-1">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">₨</span>
        <input type="number" step="{{ $step }}" min="0"
               name="{{ $name }}" id="{{ $name }}"
               value="{{ old($name, $value) }}"
               {{ $required ? 'required' : '' }}
               {{ $attributes->merge(['class' => 'block w-full rounded-md border-gray-300 pl-8 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base']) }}>
    </div>
    @if ($help)<p class="mt-1 text-xs text-gray-400">{{ $help }}</p>@endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
