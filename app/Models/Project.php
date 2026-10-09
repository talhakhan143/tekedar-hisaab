<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    /**
     * Contract types, keyed by the value stored in the column.
     * This is the single source of truth: validation and the project form
     * both read it, so adding a type here is the only edit needed.
     */
    public const CONTRACT_TYPES = [
        'structure'      => 'Structure',
        'grey_structure' => 'Gray Structure',
        'full_finished'  => 'Full Furnish',
        'interior'       => 'Interior',
        'exterior'       => 'Exterior',
        'other'          => 'Other',
    ];

    protected $guarded = [];

    protected $casts = [
        'rate_per_sqft_paisa'  => PaisaCast::class,
        'contract_value_paisa' => PaisaCast::class,
        'covered_area_sqft'    => 'decimal:2',
        'retention_percent'    => 'decimal:2',
        'completion_percent'   => 'decimal:2',
        'start_date'           => 'date',
        'expected_end_date'    => 'date',
        'actual_end_date'      => 'date',
    ];

    // ---- Relationships ----
    public function estimates(): HasMany { return $this->hasMany(Estimate::class); }
    public function clientPayments(): HasMany { return $this->hasMany(ClientPayment::class); }
    public function retentionReleases(): HasMany { return $this->hasMany(RetentionRelease::class); }
    public function materialPurchases(): HasMany { return $this->hasMany(MaterialPurchase::class); }
    public function workEntries(): HasMany { return $this->hasMany(WorkEntry::class); }
    public function workerAdvances(): HasMany { return $this->hasMany(WorkerAdvance::class); }
    public function wagePayments(): HasMany { return $this->hasMany(WagePayment::class); }
    public function otherExpenses(): HasMany { return $this->hasMany(OtherExpense::class); }

    // ---- Labels ----
    public function contractTypeLabel(): string
    {
        return self::CONTRACT_TYPES[$this->contract_type] ?? 'Full Furnish';
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active'    => 'emerald',
            'quoted'    => 'sky',
            'on_hold'   => 'amber',
            'completed' => 'indigo',
            'closed'    => 'gray',
            default     => 'gray',
        };
    }
}
