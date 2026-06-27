<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; color: #1f2937; }
    h1 { font-size: 18px; margin: 0 0 2px; }
    h2 { font-size: 13px; margin: 18px 0 4px; }
    .muted { color: #6b7280; font-size: 11px; }
    .box { display: inline-block; width: 32%; border: 1px solid #e5e7eb; padding: 8px; margin-top: 10px; }
    .box .lbl { color: #6b7280; font-size: 10px; }
    .box .val { font-size: 15px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
    td.r, th.r { text-align: right; }
    .neg { color: #dc2626; }
</style>
</head>
<body>
    @php $rs = fn($p) => 'Rs ' . number_format(\App\Support\Money::toRupees($p), 2); @endphp
    @include('reports._letterhead')
    <div class="muted">Project Closeout — {{ $project->name }} · {{ $project->client_name }} · generated {{ now()->format('d-m-Y') }}</div>

    <div class="box"><div class="lbl">Contract Value</div><div class="val">{{ $rs($project->contract_value_paisa) }}</div></div>
    <div class="box"><div class="lbl">Total Cost</div><div class="val">{{ $rs($f->totalAccruedCostPaisa()) }}</div></div>
    <div class="box"><div class="lbl">Final Profit</div><div class="val {{ $f->projectedProfitPaisa()<0?'neg':'' }}">{{ $rs($f->projectedProfitPaisa()) }}</div></div>

    <h2>Estimate vs Actual</h2>
    <table><thead><tr><th>Category</th><th class="r">Estimated</th><th class="r">Actual</th><th class="r">Variance</th></tr></thead><tbody>
        @foreach($est as $cat => $e)
            @php $a = $act[$cat] ?? 0; $var = $a - $e; @endphp
            @if($e || $a)<tr><td style="text-transform:capitalize">{{ $cat }}</td><td class="r">{{ $rs($e) }}</td><td class="r">{{ $rs($a) }}</td><td class="r {{ $var>0?'neg':'' }}">{{ $rs($var) }}</td></tr>@endif
        @endforeach
    </tbody></table>

    <h2>Money Position</h2>
    <table><tbody>
        <tr><td>Gross Billed</td><td class="r">{{ $rs($f->grossBilledPaisa()) }}</td></tr>
        <tr><td>Net Received</td><td class="r">{{ $rs($f->netReceivedPaisa()) }}</td></tr>
        <tr><td>Retention Outstanding</td><td class="r">{{ $rs($f->retentionOutstandingPaisa()) }}</td></tr>
        <tr><td>Accrued Profit</td><td class="r">{{ $rs($f->accruedProfitPaisa()) }}</td></tr>
        <tr><td>Cash Profit</td><td class="r">{{ $rs($f->cashProfitPaisa()) }}</td></tr>
    </tbody></table>
</body>
</html>
