<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralOverhead extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'amount_paisa' => PaisaCast::class,
    ];
}
