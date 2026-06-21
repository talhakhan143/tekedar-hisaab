<?php

namespace App\Models;

use App\Casts\PaisaCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'opening_balance_paisa' => PaisaCast::class,
    ];

    public function purchases(): HasMany { return $this->hasMany(MaterialPurchase::class); }

    /** Total udhaar we owe this vendor = opening balance + sum of purchase balances. */
    public function payablePaisa(): int
    {
        return (int) $this->opening_balance_paisa + (int) $this->purchases()->sum('balance_due_paisa');
    }
}
