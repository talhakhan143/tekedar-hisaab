<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkEntry extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date'                => 'date',
        'days_present'        => 'decimal:2',
        'units_done'          => 'decimal:3',
        'computed_wage_paisa' => PaisaCast::class,
    ];

    public function worker(): BelongsTo { return $this->belongsTo(Worker::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
