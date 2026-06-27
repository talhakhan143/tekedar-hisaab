@php
    use App\Support\Money;
    use App\Support\NumberToWords;
    $balance = (int) $purchase->balance_due_paisa;
    $qty = rtrim(rtrim(number_format((float) $purchase->qty, 3), '0'), '.');
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
        <div class="line muted"><span>{{ $qty }} {{ $purchase->unit }} × {{ Money::format($purchase->rate_per_unit_paisa) }}</span><span>{{ Money::format($purchase->amount_paisa) }}</span></div>
    </div>
    <hr class="hr">

    <div class="tot"><span>Total</span><span>{{ Money::format($purchase->amount_paisa) }}</span></div>
    <div class="tot"><span>Paid</span><span>{{ Money::format($purchase->amount_paid_paisa) }}</span></div>
    <div class="tot grand"><span>Balance</span><span>{{ Money::format($balance) }}</span></div>

    <div class="center" style="margin-top:6px">
        <span class="pill">{{ $balance > 0 ? 'UDHAAR / DUE' : 'FULLY PAID' }}</span>
    </div>

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees($purchase->amount_paisa) }}</div>
    @if ($purchase->notes)<div class="notes"><b>Notes:</b> {{ $purchase->notes }}</div>@endif
@endcomponent
