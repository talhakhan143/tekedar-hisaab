@php
    use App\Support\Money;
    use App\Support\NumberToWords;
@endphp
@component('vouchers.layout', [
    'docTitle' => 'Payment Receipt',
    'docNo'    => 'RCPT-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
    'docDate'  => $payment->date?->format('d-m-Y'),
    'backUrl'  => route('money-in'),
])
    <div class="line"><span class="k">Client</span><span class="v">{{ $payment->project?->client_name ?? '—' }}</span></div>
    <div class="line muted"><span>Project</span><span>{{ $payment->project?->name ?? '—' }}</span></div>
    <div class="line"><span class="k">Method</span><span class="v">{{ ucfirst($payment->payment_method) }}</span></div>
    @if ($payment->reference)<div class="line muted"><span>Ref</span><span>{{ $payment->reference }}</span></div>@endif
    @if ($payment->is_mobilization)<div class="center" style="margin:4px 0"><span class="pill">MOBILIZATION</span></div>@endif
    <hr class="hr">

    <div class="tot grand"><span>Received</span><span>{{ Money::format($payment->net_received_paisa) }}</span></div>

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees($payment->net_received_paisa) }}</div>
    @if ($payment->notes)<div class="notes"><b>Notes:</b> {{ $payment->notes }}</div>@endif
@endcomponent
