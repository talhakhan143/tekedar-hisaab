@php
    use App\Support\Money;
    use App\Support\NumberToWords;
@endphp
@component('vouchers.layout', [
    'docTitle' => 'Wage Voucher',
    'docNo'    => 'WAGE-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
    'docDate'  => $payment->date?->format('d-m-Y'),
    'backUrl'  => $payment->worker ? route('workers.show', $payment->worker) : route('workers.index'),
])
    <div class="line"><span class="k">Worker</span><span class="v">{{ $payment->worker?->name ?? '—' }}@if($payment->worker) #{{ $payment->worker->id }}@endif</span></div>
    @if ($payment->worker?->role)<div class="line muted"><span>Role</span><span>{{ ucfirst($payment->worker->role) }}</span></div>@endif
    <div class="line"><span class="k">Project</span><span class="v">{{ $payment->project?->name ?? '— general —' }}</span></div>
    @if ($payment->period_label)<div class="line muted"><span>Period</span><span>{{ $payment->period_label }}</span></div>@endif
    <hr class="hr">

    <div class="tot grand"><span>Paid</span><span>{{ Money::format($payment->amount_paisa) }}</span></div>

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees($payment->amount_paisa) }}</div>

    @if ($payment->worker)
        <div class="line muted"><span>Remaining payable</span><span>{{ Money::format($payment->worker->payablePaisa()) }}</span></div>
    @endif
    @if ($payment->notes)<div class="notes"><b>Notes:</b> {{ $payment->notes }}</div>@endif
@endcomponent
