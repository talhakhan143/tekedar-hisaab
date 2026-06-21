<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialPurchase extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date'                => 'date',
        'qty'                 => 'decimal:3',
        'rate_per_unit_paisa' => PaisaCast::class,
        'amount_paisa'        => PaisaCast::class,
        'amount_paid_paisa'   => PaisaCast::class,
        'balance_due_paisa'   => PaisaCast::class,
        'wastage_qty'         => 'decimal:3',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
}
