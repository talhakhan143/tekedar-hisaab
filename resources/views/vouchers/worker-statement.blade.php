@php
    use App\Support\Money;
    use App\Support\NumberToWords;
    $daysFmt = rtrim(rtrim(number_format($days, 1), '0'), '.');
@endphp
@component('vouchers.layout', [
    'docTitle' => 'Worker Statement',
    'docNo'    => 'WS-' . str_pad($worker->id, 5, '0', STR_PAD_LEFT),
    'docDate'  => now()->format('d-m-Y'),
    'backUrl'  => route('workers.show', $worker),
])
    <div class="line"><span class="k">Worker</span><span class="v">{{ $worker->name }} #{{ $worker->id }}</span></div>
    <div class="line muted"><span>Role</span><span>{{ ucfirst($worker->role) }}</span></div>
    <div class="line"><span class="k">Project</span><span class="v">{{ $project?->name ?? 'ALL projects' }}</span></div>
    <hr class="hr">

    <div class="tot"><span>Days worked</span><span>{{ $daysFmt }}</span></div>
    <div class="tot"><span>Earned</span><span>{{ Money::format($earned) }}</span></div>
    <div class="tot"><span>Paid</span><span>{{ Money::format($paid) }}</span></div>
    <div class="tot"><span>Advance (net)</span><span>{{ Money::format($advNet) }}</span></div>
    <hr class="hr">

    @if ($payable >= 0)
        <div class="tot grand"><span>Baqi (Dena)</span><span>{{ Money::format($payable) }}</span></div>
    @else
        <div class="tot grand"><span>Advance extra</span><span>{{ Money::format(-$payable) }}</span></div>
    @endif

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees(abs($payable)) }}</div>
@endcomponent
