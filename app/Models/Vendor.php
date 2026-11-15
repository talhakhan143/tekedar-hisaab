<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    /**
     * Vendor ki qismein. Column ab saada string hai (dekho migration
     * 2026_11_15_100001_make_vendor_type_free_text.php), is liye jaiz values
     * ka faisla yahin se hota hai. Jahan bhi type validate ho, is list se ho.
     */
    public const TYPES = [
        'supplier'      => 'Supplier',
        'subcontractor' => 'Sub-contractor',
        'driver'        => 'Driver',
    ];

    protected $guarded = [];

    protected $casts = [
        'opening_balance_paisa' => PaisaCast::class,
    ];

    /**
     * Dikhane wala naam. Agar DB me koi purani ya anjaan value pari ho to
     * khaali jagah dikhane ke bajaye usay hi saaf kar ke dikha dete hain.
     */
    public function typeLabel(): string
    {
        $type = (string) $this->type;

        return self::TYPES[$type] ?? ($type === '' ? '—' : ucfirst(str_replace('_', ' ', $type)));
    }

    public function purchases(): HasMany { return $this->hasMany(MaterialPurchase::class); }

    /** Is driver ke kiraya wale chakkar (jin purchases par ye driver laga tha). */
    public function trips(): HasMany { return $this->hasMany(MaterialPurchase::class, 'driver_id'); }

    /** Is driver ko kitna kiraya (کرایہ) bana, saare chakkaron ka jor. */
    public function freightPaisa(): int
    {
        return (int) $this->trips()->sum('freight_paisa');
    }

    /** Is driver ko ki gayi kiraya payments. */
    public function driverPayments(): HasMany
    {
        return $this->hasMany(DriverPayment::class, 'driver_id');
    }

    /** Is driver ko kiraya me se kitna de diya gaya. */
    public function driverPaidPaisa(): int
    {
        return (int) $this->driverPayments()->sum('amount_paisa');
    }

    /**
     * Driver ka baqi kiraya = kul kiraya − jo diya.
     *
     * Mazdoor ka model bhi bilkul yehi hai (kamaya − diya = baqi). Ye raqam
     * payablePaisa() se alag hai aur us se dohri nahi hoti, kyunke jis purchase
     * par driver laga ho us ka kiraya MaterialPurchase ke saving hook me
     * supplier ke balance_due_paisa se nikal dia jata hai.
     */
    public function driverPayablePaisa(): int
    {
        return $this->freightPaisa() - $this->driverPaidPaisa();
    }

    /** Is vendor ka kul dena: supplier/theka ka udhaar + driver ka baqi kiraya. */
    public function totalDuePaisa(): int
    {
        return $this->payablePaisa() + $this->driverPayablePaisa();
    }

    /**
     * Total udhaar we owe this vendor = opening balance + sum of purchase balances.
     *
     * KHABARDAR, isay "theek" karne ki koshish na karo: ye jor sirf
     * purchases() par lagta hai, yani vendor_id par. Driver ka kiraya is me
     * jaan boojh kar nahi ginte. Kiraya pehle hi usi purchase ke bill
     * (amount_paisa) aur us ke balance_due_paisa ka hissa hai, jo supplier ke
     * khaate me chala jata hai. Agar driver ko doosra payable de dein to wohi
     * ek raqam do dafa ginni jayegi (DOUBLE COUNT) aur kul udhaar jhoota barh
     * jayega. Driver ka payable sirf us ka opening balance hai.
     */
    public function payablePaisa(): int
    {
        return (int) $this->opening_balance_paisa + (int) $this->purchases()->sum('balance_due_paisa');
    }
}
