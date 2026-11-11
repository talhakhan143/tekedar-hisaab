<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[\Illuminate\Database\Eloquent\Attributes\ScopedBy(\App\Models\Scopes\LiveProjectScope::class)]
class MaterialPurchase extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'date'                => 'date',
        'qty'                 => 'decimal:3',
        'rate_per_unit_paisa' => PaisaCast::class,
        'goods_amount_paisa'  => PaisaCast::class,
        'freight_paisa'       => PaisaCast::class,
        'labour_paisa'        => PaisaCast::class,
        'other_charges_paisa' => PaisaCast::class,
        'amount_paisa'        => PaisaCast::class,
        'amount_paid_paisa'   => PaisaCast::class,
        'balance_due_paisa'   => PaisaCast::class,
        'wastage_qty'         => 'decimal:3',
    ];

    /**
     * Ek hi jagah par row ke usool (invariants) lagate hain, taake koi bhi
     * aane wala insert/update raasta inhein tor na sake:
     *
     *   amount_paisa      = goods + kiraya + mazdoori + deegar   (poora bill)
     *   balance_due_paisa = amount_paisa - amount_paid_paisa      (udhaar)
     *
     * Controller bhi yehi hisaab karta hai, is liye ye hook us se larta nahi,
     * sirf tasdeeq kar deta hai. pay() bhi theek chalta hai: wo paid barhata
     * hai aur balance utna hi ghatata hai, hook wahi raqam dobara nikalta hai.
     */
    protected static function booted(): void
    {
        static::saving(function (self $purchase): void {
            $attrs = $purchase->getAttributes();

            // Aadhi loaded row (jaise select('id')->get()) par hisaab lagana
            // tabahi hoti: amount_paisa gayab hoti aur hum bill ko zero kar
            // dete. Aisi surat me haath nahi lagate.
            if ($purchase->exists && ! array_key_exists('amount_paisa', $attrs)) {
                return;
            }

            $freight = (int) ($purchase->freight_paisa ?? 0);
            $labour  = (int) ($purchase->labour_paisa ?? 0);
            $other   = (int) ($purchase->other_charges_paisa ?? 0);

            $purchase->freight_paisa       = $freight;
            $purchase->labour_paisa        = $labour;
            $purchase->other_charges_paisa = $other;

            // Ehtiyat: PaisaCast::set() null aur '' ko 0 bana deta hai, is liye
            // cast ke zariye parhne par "goods set hui thi ya nahi" kabhi pata
            // nahi chalta, 0 aur NULL ek jaise lagte hain. Is liye raw
            // attribute dekhte hain: key mojood na ho ya asal NULL ho, tab hi
            // goods ko bill me se nikalte hain.
            $goods = array_key_exists('goods_amount_paisa', $attrs) && $attrs['goods_amount_paisa'] !== null
                ? (int) $attrs['goods_amount_paisa']
                : null;

            if ($goods === null) {
                $goods = max(0, (int) ($purchase->amount_paisa ?? 0) - $freight - $labour - $other);
            }

            $amount = $goods + $freight + $labour + $other;

            $purchase->goods_amount_paisa = $goods;
            $purchase->amount_paisa       = $amount;
            $purchase->balance_due_paisa  = $amount - (int) ($purchase->amount_paid_paisa ?? 0);
        });
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
}
