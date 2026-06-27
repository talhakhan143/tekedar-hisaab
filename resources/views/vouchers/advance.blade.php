@php
    use App\Support\Money;
    use App\Support\NumberToWords;
    $isRecovery = $advance->type === 'recovery';
@endphp
@component('vouchers.layout', [
    'docTitle' => $isRecovery ? 'Recovery Voucher' : 'Advance Voucher',
    'docNo'    => 'ADV-' . str_pad($advance->id, 5, '0', STR_PAD_LEFT),
    'docDate'  => $advance->date?->format('d-m-Y'),
    'backUrl'  => $advance->worker ? route('workers.show', $advance->worker) : route('workers.index'),
])
    <div class="line"><span class="k">Worker</span><span class="v">{{ $advance->worker?->name ?? '—' }}@if($advance->worker) #{{ $advance->worker->id }}@endif</span></div>
    @if ($advance->worker?->role)<div class="line muted"><span>Role</span><span>{{ ucfirst($advance->worker->role) }}</span></div>@endif
    <div class="line"><span class="k">Project</span><span class="v">{{ $advance->project?->name ?? '— general —' }}</span></div>
    <div class="center" style="margin:4px 0"><span class="pill">{{ $isRecovery ? 'RECOVERY' : 'ADVANCE GIVEN' }}</span></div>
    <hr class="hr">

    <div class="tot grand"><span>{{ $isRecovery ? 'Recovered' : 'Given' }}</span><span>{{ Money::format($advance->amount_paisa) }}</span></div>

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees($advance->amount_paisa) }}</div>

    @if ($advance->worker)
        <div class="line muted"><span>Advance balance</span><span>{{ Money::format($advance->worker->advancesOutstandingPaisa()) }}</span></div>
    @endif
    @if ($advance->notes)<div class="notes"><b>Notes:</b> {{ $advance->notes }}</div>@endif
@endcomponent
