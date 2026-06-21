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
        'default_retention'   => '7',     // percent
        'default_wastage'     => '5',     // percent
        'allocate_overheads'  => '0',     // 1 = pro-rata across active projects
    ];

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
