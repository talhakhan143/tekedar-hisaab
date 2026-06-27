@php
    use App\Support\Money;
    use App\Support\NumberToWords;
@endphp
@component('vouchers.layout', [
    'docTitle' => 'Expense Voucher',
    'docNo'    => 'EXP-' . str_pad($expense->id, 5, '0', STR_PAD_LEFT),
    'docDate'  => $expense->date?->format('d-m-Y'),
    'backUrl'  => $expense->project ? route('projects.show', ['project' => $expense->project, 'tab' => 'expenses']) : route('money-out'),
])
    <div class="line"><span class="k">Category</span><span class="v">{{ ucfirst(str_replace('_',' ', $expense->category)) }}</span></div>
    <div class="line"><span class="k">Project</span><span class="v">{{ $expense->project?->name ?? '— general —' }}</span></div>
    @if (!empty($expense->paid_to))<div class="line muted"><span>Paid to</span><span>{{ $expense->paid_to }}</span></div>@endif
    @if (!empty($expense->description))<div class="line muted"><span>Detail</span><span>{{ $expense->description }}</span></div>@endif
    <hr class="hr">

    <div class="tot grand"><span>Amount</span><span>{{ Money::format($expense->amount_paisa) }}</span></div>

    <div class="words"><b>In words:</b> {{ NumberToWords::rupees($expense->amount_paisa) }}</div>
@endcomponent
