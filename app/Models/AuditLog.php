<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public static function record(string $action, ?Model $subject = null, ?string $description = null, array $old = [], array $new = []): self
    {
        return static::create([
            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->getKey(),
            'action'       => $action,
            'description'  => $description,
            'old_values'   => $old ?: null,
            'new_values'   => $new ?: null,
            'user_id'      => auth()->id(),
        ]);
    }
}
