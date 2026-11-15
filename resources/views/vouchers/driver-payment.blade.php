@php
    use App\Support\Money;
    use App\Support\NumberToWords;
@endphp
@component('vouchers.layout', [
    'docTitle' => 'Kiraya Voucher',
    'docNo'    => 'KIRAYA-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT),
    'docDate'  => $payment->date?->format('d-m-Y'),
    'backUrl'  => $payment->driver ? route('vendors.show', $payment->driver) : route('vendors.index'),
])
    <div class="line"><span class="k">Driver</span><span class="v">{{ $payment->driver?->name ?? '—' }}@if($payment->driver) #{{ $payment->driver->id }}@endif</span></div>
    @if ($payment->driver?->phone)<div class="line muted"><span>Phone</span><span>{{ $payment->driver->phone }}</span></div>@endif
    <div class="line"><span class="k">Project</span><span class="v">{{ $payment->project?->name ?? '— general —' }}</span></div>
    @if ($payment->payment_method)<div class="line muted"><span>Kaise diya</span><span>{{ ucfirst($payment->payment_method) }}</span></div>@endif
    <hr class="hr">

    <div class="tot grand"><span>Kiraya diya</span><span>{{ Money::format($payment->amount_paisa) }}</span></div>

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees($payment->amount_paisa) }}</div>

    @if ($payment->driver)
        {{-- Baqi kiraya = kul kiraya minus jo diya. Supplier ka udhaar is me
             nahi aata, wo alag khaata hai. --}}
        <div class="line muted"><span>Kul kiraya bana</span><span>{{ Money::format($payment->driver->freightPaisa()) }}</span></div>
        <div class="line muted"><span>Ab tak diya</span><span>{{ Money::format($payment->driver->driverPaidPaisa()) }}</span></div>
        <div class="line"><span class="k">Baqi kiraya</span><span class="v">{{ Money::format($payment->driver->driverPayablePaisa()) }}</span></div>
    @endif
    @if ($payment->notes)<div class="notes"><b>Notes:</b> {{ $payment->notes }}</div>@endif
@endcomponent
