<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPayment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date'                 => 'date',
        'gross_amount_paisa'   => PaisaCast::class,
        'retention_held_paisa' => PaisaCast::class,
        'net_received_paisa'   => PaisaCast::class,
        'is_mobilization'      => 'boolean',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
