<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'qty_estimated'       => 'decimal:3',
        'rate_per_unit_paisa' => PaisaCast::class,
        'amount_paisa'        => PaisaCast::class,
        'wastage_percent'     => 'decimal:2',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }

    /** Expected qty after wastage inflation (material). */
    public function expectedQtyWithWastage(): float
    {
        return (float) $this->qty_estimated * (1 + ((float) $this->wastage_percent / 100));
    }
}
