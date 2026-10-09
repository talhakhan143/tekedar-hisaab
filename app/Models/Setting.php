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
