<?php

namespace App\Models;

use App\Casts\PaisaCast;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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

    // ---- Ek hi banda do dafa add hone se rokna ----

    /**
     * Naam ka saada roop: aage peeche ki space hatao, beech ki kitni bhi
     * space ho ek kar do, aur sab chhote harf me.
     *
     * Ye is liye hai ke mazdoor is app me global hai, har project me wahi
     * list dikhti hai. Agar wahi banda dobara "Salman" likh kar add ho jaye
     * to us ki nayi id ban jati hai, aur phir us ka hisaab do khaaton me bat
     * jata hai: haazri ek id par lagti hai, peshgi doosri par. Report dono ko
     * alag alag banda samajh kar har khaata kam dikhati hai. Is liye "Salman",
     * "  sAlMaN  " aur "Salman   Khan" ko aik hi naam gina jata hai.
     */
    public static function normaliseName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }

    /**
     * Wo workers (delete kiye hue nahi) jin ka naam is naam se milta hai.
     *
     * $exceptId dene par wo banda khud ginti me nahi aata. Ye rename ke liye
     * laazmi hai: warna kisi ka naam waise hi rakh kar save karna apne hi aap
     * se takkar ban jata aur reject ho jata. Yehi bug attendanceExists() me
     * bhi tha, wahan $ignoreId se theek kia gaya tha.
     *
     * Poori list PHP me chhanti jati hai kyunki beech ki space ko SQL me
     * portably nahi nichora ja sakta. Ye sirf naya banda banate ya naam
     * badalte waqt chalta hai, is liye mehnga nahi.
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function sameNameAs(string $name, ?int $exceptId = null): Collection
    {
        $needle = static::normaliseName($name);

        if ($needle === '') {
            return collect();
        }

        return static::query()
            ->when($exceptId !== null, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('id')
            ->get()
            ->filter(fn (self $w) => static::normaliseName((string) $w->name) === $needle)
            ->values();
    }

    /**
     * Wahi naam, magar hataye gaye (soft deleted) mazdooron me.
     *
     * Ye alag is liye hai ke hataya hua banda kisi total me ginta nahi, yani
     * paisa ghalat nahi hota. Magar agar maalik ne ghalti se hataya tha aur ab
     * wahi naam dobara likh raha hai, to usay khaali khaata milega aur purani
     * haazri aur adaigi chupke se peeche reh jayegi. Is liye batana zaroori hai.
     */
    public static function trashedSameNameAs(string $name): Collection
    {
        $needle = static::normaliseName($name);

        if ($needle === '') {
            return collect();
        }

        return static::onlyTrashed()
            ->orderBy('id')
            ->get()
            ->filter(fn (self $w) => static::normaliseName((string) $w->name) === $needle)
            ->values();
    }

    /** Hataye gaye hum-naam bande ke bare me ek line, warna khaali string. */
    public static function trashedNameHint(string $name): string
    {
        $gone = static::trashedSameNameAs($name);

        if ($gone->isEmpty()) {
            return '';
        }

        $ids = $gone->map(fn (self $w) => '#' . $w->id)->implode(', ');

        return ' Dhyan rahe: isi naam ka banda (' . $ids . ') pehle hataya ja chuka hai.'
            . ' Naya banaoge to us ki purani haazri aur adaigi us purane khaate me hi padi rehti hai, is naye me nahi aati.'
            . ' Agar wo ghalti se hata tha to naya banane se pehle usay wapis bahaal karwa lo.'
            . ' Waqai naya banda hai to "Alag banda hai" par tick kar ke dobara dabao.';
    }

    /**
     * Wo paigham jo maalik ko dikhta hai jab wahi naam dobara likha jaye.
     *
     * Dono jagah (project page ka jaldi-add aur poora worker form) aik hi
     * alfaaz aane chahiyen, is liye yahan aik hi jagah bana hai. $advice me
     * har jagah ka apna mashwara aata hai.
     *
     * @param  \Illuminate\Support\Collection<int, static>  $matches
     */
    public static function duplicateNameMessage(string $name, Collection $matches, string $advice): string
    {
        $list = $matches
            ->map(fn (self $w) => '#' . $w->id . ', ' . $w->role . ', baqi ' . Money::format($w->payablePaisa(), true, false))
            ->implode('), (');

        $head = $matches->count() === 1
            ? trim($name) . ' naam ka mazdoor pehle se hai'
            : trim($name) . ' naam ke mazdoor pehle se hain';

        return $head . ' (' . $list . '). ' . $advice
            . " Agar ye waqai doosra banda hai to 'Alag banda hai' par tick kar ke dobara dabao.";
    }
}
