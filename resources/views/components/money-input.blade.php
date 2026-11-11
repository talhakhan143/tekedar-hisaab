@props(['name', 'label', 'value' => null, 'required' => false, 'help' => null, 'step' => '0.01'])

{{--
    Paison wala input. Neeche live "itna banta hai" wali line is liye lagi hai ke
    ek zero chhoot jana sab se aam ghalti hai: 30,00,000 ki jagah 3,00,000 type ho
    jaye to form khamoshi se maan leta tha. Ab likha aata hai "= 30.00 Lac", jo ek
    nazar me pakda jata hai.

    Grouping jaan boojh kar 'en-IN' hai, 'en-PK' nahi: en-PK western grouping deta
    hai (3,000,000) jo thekedaar ghalat parhta hai. en-IN ki grouping Pakistani
    convention (30,00,000) se bilkul milti hai, aur PHP ki Money::format() bhi
    wahi karti hai, is liye dono jagah ek hi shakal nazar aati hai.
--}}
<div x-data="{
        raw: @js((string) old($name, $value)),
        get n() { const v = Number(String(this.raw).replace(/[^\d.\-]/g, '')); return isFinite(v) ? v : 0 },
        get grouped() { return this.n.toLocaleString('en-IN', { maximumFractionDigits: 2 }) },
        get scale() {
            const a = Math.abs(this.n);
            if (a >= 10000000) return (a / 10000000).toFixed(2) + ' Cr';
            if (a >= 100000)   return (a / 100000).toFixed(2) + ' Lac';
            if (a >= 1000)     return (a / 1000).toFixed(1) + 'K';
            return '';
        }
     }">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>
    <div class="relative mt-1">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">₨</span>
        {{--
            Yahan jaan boojh kar x-model NAHI lagaya. Bulane wale kai form isi
            input par apna x-model.number="rate" wagera bhejte hain
            ($attributes ke zariye), to ek hi input par do x-model aa jate aur
            dono input ki value ek doosre par likhne lagte. @input se hum sirf
            parhte hain, likhte nahi, is liye caller ka x-model jyun ka tyun
            chalta rehta hai.
        --}}
        <input type="number" step="{{ $step }}" min="0"
               name="{{ $name }}" id="{{ $name }}"
               @input="raw = $event.target.value"
               value="{{ old($name, $value) }}"
               {{ $required ? 'required' : '' }}
               {{ $attributes->merge(['class' => 'block w-full rounded-md border-gray-300 pl-8 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-base']) }}>
    </div>
    <p class="mt-1 text-xs font-semibold text-emerald-700" x-show="n > 0" x-cloak>
        = ₨ <span x-text="grouped"></span><template x-if="scale"><span> &nbsp;·&nbsp; <span x-text="scale"></span></span></template>
    </p>
    @if ($help)<p class="mt-1 text-xs text-gray-400">{{ $help }}</p>@endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
