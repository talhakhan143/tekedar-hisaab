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
        'common_items'        => 'cement,sarya,block,crush,sand,bajri,rait,tile,paint',
        'common_units'        => 'bag,ton,sft,cft,truck,trip,nos,kg,ft',
    ];

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

    public static function get(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if ($row) {
            return $row->value;
        }
        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function all_settings(): array
    {
        $stored = static::pluck('value', 'key')->toArray();
        return array_merge(self::DEFAULTS, $stored);
    }
}
