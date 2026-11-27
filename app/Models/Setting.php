<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    public $timestamps = true;

    /** Defaults used when a key has not been saved yet. */
    public const DEFAULTS = [
        'company_name'        => 'Tekedar Hisaab',
        'company_logo'        => null,
        'company_address'     => null,
        'company_phone'       => null,
        'default_wastage'     => '5',     // percent
        'allocate_overheads'  => '0',     // 1 = pro-rata across active projects
        'worker_roles'        => 'mistri,mazdoor,electrician,plumber,painter,foreman,other',
        // Har project me aane wali cheezein, chahe kisi bhi qism ka kaam ho.
        'common_items'        => 'cement,reta,crush,bajri,keel,bending wire,pani',
        'common_units'        => 'bag,ton,sft,cft,truck,trip,nos,kg,ft',

        // Kaam ki qism ke hisaab se cheezon ki list. Project ki contract_type
        // dekh kar usi ki list suggest hoti hai, taake har bar poora naam
        // likhna na pare aur imla bhi ek jaisi rahe (warna "Cemnt" aur "cement"
        // do alag dher ban jate hain).
        'items_structure'      => 'sarya,steel,toss block,aam block,eent,shuttering,girder,dpc sheet,kharanja,pipe,concrete,lintel',
        'items_grey_structure' => 'sarya,steel,toss block,aam block,eent,shuttering,girder,dpc sheet,kharanja,pipe,concrete,lintel,plaster,chhat ka sarya',
        'items_interior'       => 'tile,marble,paint,colour,putty,primer,distemper,gypsum,false ceiling,pop,plywood,darwaza,khirki,glass,lock,handle,electric wire,switch,socket,db board,light,fan,pvc pipe,basin,commode,tap,shower,silicone,thinner,polish',
        'items_exterior'       => 'weather coat,exterior paint,tile,stone,marble,plaster,grill,gate,railing,drain pipe,kerb stone,putty,primer,mosaic',
        'items_full_finished'  => 'sarya,steel,toss block,aam block,eent,shuttering,girder,dpc sheet,pipe,concrete,lintel,plaster,tile,marble,paint,putty,primer,gypsum,false ceiling,plywood,darwaza,khirki,glass,electric wire,switch,socket,db board,light,fan,pvc pipe,basin,commode,tap,shower,weather coat,grill,gate',
        'items_other'          => 'tile,paint,pipe,wire,plywood,lohaa,plaster',
    ];

    /** Kaam ki har qism ki apni list, Settings me isi key se save hoti hai. */
    public static function itemsKeyFor(?string $contractType): string
    {
        $type = (string) $contractType;

        return array_key_exists('items_' . $type, self::DEFAULTS) ? 'items_' . $type : 'items_other';
    }

    /** Worker role categories as a clean array (editable from Settings). */
    public static function workerRoles(): array
    {
        $raw = static::get('worker_roles');
        $roles = collect(explode(',', (string) $raw))
            ->map(fn ($r) => trim($r))
            ->filter()
            ->map(fn ($r) => strtolower($r))
            ->unique()
            ->values()
            ->all();
        return $roles ?: ['mazdoor', 'other'];
    }

    /**
     * Wo cheezein jo maalik baar baar likhta hai (cement, sarya, block...).
     * Sirf dropdown bharne ke liye hain, form me koi bhi naam likha ja sakta
     * hai. worker_roles ki tarah lowercase NAHI karte: ye categories nahi,
     * naam hain, aur maalik ne jaise likha hai waise hi dikhne chahiyen.
     */
    public static function commonItems(): array
    {
        $items = static::splitList(static::get('common_items'));

        return $items ?: static::splitList(self::DEFAULTS['common_items']);
    }

    /**
     * Kisi qism ke kaam ki cheezein: us qism ki apni list + har project wali
     * common list. Dono mila kar dete hain, warna cement jaisi cheez har list
     * me alag se likhni parti.
     */
    public static function itemsForContractType(?string $contractType): array
    {
        $key  = static::itemsKeyFor($contractType);
        $own  = static::splitList(static::get($key)) ?: static::splitList(self::DEFAULTS[$key]);
        $base = static::commonItems();

        // Ek hi cheez dono list me ho to ek hi dafa aaye. Milan chhote harf par
        // hota hai magar dikhta wahi hai jo pehle likha gaya tha.
        $seen = [];
        $out  = [];
        foreach (array_merge($base, $own) as $item) {
            $k = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $item)));
            if ($k === '' || isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $item;
        }

        return $out;
    }

    /** Aam units (bag, ton, sft...). Case waise hi rehta hai jaisa likha gaya. */
    public static function commonUnits(): array
    {
        $units = static::splitList(static::get('common_units'));

        return $units ?: static::splitList(self::DEFAULTS['common_units']);
    }

    /** Comma wali list ko saaf array banata hai: trim, khaali nikal do, unique. */
    protected static function splitList($raw): array
    {
        return collect(explode(',', (string) $raw))
            ->map(fn ($x) => trim($x))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ek request ke andar yaad rakhi hui settings.
     *
     * Item ki list har project ke liye alag nikalti hai, aur har dafa wahi do
     * teen settings dobara DB se parhna fuzool hai: 12 projects par ye 37
     * queries ban rahi thin. Ab har key ek hi dafa parhti hai.
     */
    protected static array $memo = [];

    public static function get(string $key, $default = null)
    {
        if (! array_key_exists($key, static::$memo)) {
            static::$memo[$key] = static::where('key', $key)->value('value');
        }

        $value = static::$memo[$key];

        if ($value !== null) {
            return $value;
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        // Likhne ke baad yaad hui purani value phenk do, warna usi request me
        // aage purana jawab milta rehta hai (settings save karte hi ye hota).
        unset(static::$memo[$key]);
    }

    /** Test ya kisi lambe chalne wale kaam ke liye yaaddasht saaf karo. */
    public static function forgetMemo(): void
    {
        static::$memo = [];
    }

    public static function all_settings(): array
    {
        $stored = static::pluck('value', 'key')->toArray();
        return array_merge(self::DEFAULTS, $stored);
    }
}
