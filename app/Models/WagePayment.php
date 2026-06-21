<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WagePayment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date'         => 'date',
        'amount_paisa' => PaisaCast::class,
    ];

    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
