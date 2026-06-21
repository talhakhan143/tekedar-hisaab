<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worker extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'default_wage_paisa' => PaisaCast::class,
    ];

    public function workEntries(): HasMany { return $this->hasMany(WorkEntry::class); }
    public function advances(): HasMany { return $this->hasMany(WorkerAdvance::class); }
    public function wagePayments(): HasMany { return $this->hasMany(WagePayment::class); }

    // ---- Ledger ----
    public function earnedPaisa(): int { return (int) $this->workEntries()->sum('computed_wage_paisa'); }
    public function paidPaisa(): int { return (int) $this->wagePayments()->sum('amount_paisa'); }

    /** Net advances outstanding = advance_given - recovery. */
    public function advancesOutstandingPaisa(): int
    {
        $given = (int) $this->advances()->where('type', 'advance_given')->sum('amount_paisa');
        $recovered = (int) $this->advances()->where('type', 'recovery')->sum('amount_paisa');
        return $given - $recovered;
    }

    /** Payable now = earned - paid - advances outstanding. */
    public function payablePaisa(): int
    {
        return $this->earnedPaisa() - $this->paidPaisa() - $this->advancesOutstandingPaisa();
    }

    public function roleLabel(): string
    {
        return ucfirst($this->role);
    }
}
