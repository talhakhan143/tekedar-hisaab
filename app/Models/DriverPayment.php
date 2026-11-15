<?php

namespace App\Models;

use App\Casts\PaisaCast;
use App\Models\Scopes\LiveProjectScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Driver ko diya gaya kiraya. Mazdoor ke WagePayment ka theek wahi jorr.
 *
 * Hataye gaye project ka paisa kisi total me nahi aana chahiye, is liye
 * LiveProjectScope yahan bhi lagi hai (project_id NULL wali rows hamesha
 * shaamil rehti hain, kyunke NULL ka matlab "kisi project ka nahi" hai).
 */
#[ScopedBy(LiveProjectScope::class)]
class DriverPayment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date'         => 'date',
        'amount_paisa' => PaisaCast::class,
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'driver_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
