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

    /**
     * Baqi rakam, project ke hisaab se alag alag, ek ya kai workers ke liye.
     *
     * Ye deliberately aik hi jagah hai: project page ka form bhi yahi parhta
     * hai aur payment save karte waqt validation bhi, warna screen par aik
     * number dikhta aur submit par doosra reject karta.
     *
     * Key 0 ka matlab "bina project" hai, kyunki teenon ledger tables me
     * project_id nullable hai. Saare buckets jama karo to natija bilkul
     * payablePaisa() ke barabar aata hai.
     *
     * @param  array<int>|null  $workerIds  null = saare workers
     * @return array<int, array<int, int>>  [worker_id => [project_id => baqi_paisa]]
     */
    public static function duesByProjectFor(?array $workerIds = null): array
    {
        $buckets = [];

        $add = function (string $table, string $column, int $sign, ?string $type) use (&$buckets, $workerIds) {
            $model = match ($table) {
                'work_entries'    => WorkEntry::query(),
                'wage_payments'   => WagePayment::query(),
                'worker_advances' => WorkerAdvance::query(),
            };

            $rows = $model
                ->when($workerIds !== null, fn ($q) => $q->whereIn('worker_id', $workerIds))
                ->when($type !== null, fn ($q) => $q->where('type', $type))
                ->selectRaw("worker_id, project_id, SUM({$column}) as total")
                ->groupBy('worker_id', 'project_id')
                ->get();

            foreach ($rows as $row) {
                $pid = (int) ($row->project_id ?? 0);
                $buckets[(int) $row->worker_id][$pid] =
                    ($buckets[(int) $row->worker_id][$pid] ?? 0) + ($sign * (int) $row->total);
            }
        };

        $add('work_entries', 'computed_wage_paisa', 1, null);
        $add('wage_payments', 'amount_paisa', -1, null);
        $add('worker_advances', 'amount_paisa', -1, 'advance_given');
        $add('worker_advances', 'amount_paisa', 1, 'recovery');

        return $buckets;
    }

    public function roleLabel(): string
    {
        return ucfirst($this->role);
    }
}
