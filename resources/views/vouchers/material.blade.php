@php
    use App\Support\Money;
    use App\Support\NumberToWords;
    $balance = (int) $purchase->balance_due_paisa;
    $qty = rtrim(rtrim(number_format((float) $purchase->qty, 3), '0'), '.');

    /*
     * Ye parchi vendor ke haath me jati hai, is liye hisaab aankhon se mil jana
     * chahiye: pehle maal (qty × rate), phir uske upar laga kiraya / mazdoori /
     * deegar, phir kul total. amount_paisa pehle ki tarah GRAND total hai.
     */
    $freight = (int) $purchase->freight_paisa;
    $labour  = (int) $purchase->labour_paisa;
    $other   = (int) $purchase->other_charges_paisa;
    $extras  = $freight + $labour + $other;
    $grand   = (int) $purchase->amount_paisa;

    // Purani rows me goods column bhara hua nahi hota. Aise me maal ki raqam grand
    // total me se charges nikal kar banti hai, taake parchi har haalat me jor pori
    // kare (maal + charges == kul total) aur item line par 0 na chhape.
    $goods = (int) $purchase->goods_amount_paisa ?: ($grand - $extras);
@endphp
@component('vouchers.layout', [
    'docTitle' => 'Purchase Invoice',
    'docNo'    => 'PUR-' . str_pad($purchase->id, 5, '0', STR_PAD_LEFT),
    'docDate'  => $purchase->date?->format('d-m-Y'),
    'backUrl'  => route('materials.index'),
])
    <div class="line"><span class="k">Vendor</span><span class="v">{{ $purchase->vendor?->name ?? '—' }}</span></div>
    <div class="line"><span class="k">Project</span><span class="v">{{ $purchase->project?->name ?? '— general —' }}</span></div>
    <hr class="hr">

    <div class="item">
        <div class="nm">{{ $purchase->item_name }}</div>
        <div class="line muted"><span>{{ $qty }} {{ $purchase->unit }} × {{ Money::format($purchase->rate_per_unit_paisa) }}</span><span>{{ Money::format($goods) }}</span></div>
    </div>
    <hr class="hr">

    @if ($freight > 0)<div class="tot"><span>+ Kiraya (کرایہ)</span><span>{{ Money::format($freight) }}</span></div>@endif
    @if ($labour > 0)<div class="tot"><span>+ Mazdoori (مزدوری)</span><span>{{ Money::format($labour) }}</span></div>@endif
    @if ($other > 0)<div class="tot"><span>+ Deegar (دیگر)</span><span>{{ Money::format($other) }}</span></div>@endif

    <div class="tot grand"><span>Kul Total</span><span>{{ Money::format($grand) }}</span></div>
    <div class="tot"><span>Paid</span><span>{{ Money::format($purchase->amount_paid_paisa) }}</span></div>
    <div class="tot grand"><span>Balance</span><span>{{ Money::format($balance) }}</span></div>

    <div class="center" style="margin-top:6px">
        <span class="pill">{{ $balance > 0 ? 'UDHAAR / DUE' : 'FULLY PAID' }}</span>
    </div>

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees($grand) }}</div>
    @if ($purchase->notes)<div class="notes"><b>Notes:</b> {{ $purchase->notes }}</div>@endif
@endcomponent
