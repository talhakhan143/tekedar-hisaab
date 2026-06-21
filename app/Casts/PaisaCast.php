<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a money column. Stored as integer paisa in the DB.
 *
 * On the way IN, accepts either:
 *   - an integer/string already in paisa  -> stored as-is
 *   - (NOTE) we DO NOT auto-convert rupees here; controllers convert form input
 *     via Money::toPaisa() before saving, so the model always speaks paisa.
 *
 * This cast mainly guarantees the value is an integer and never a float.
 */
class PaisaCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        // Defensive: if a float sneaks in, round it. Integers pass through.
        return (int) round((float) $value);
    }
}
