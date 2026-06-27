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
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
    td.r, th.r { text-align: right; }
</style>
</head>
<body>
    @php $rs = fn($p) => 'Rs ' . number_format(\App\Support\Money::toRupees($p), 2); @endphp
    @include('reports._letterhead')
    <div class="muted">Outstanding Report · generated {{ now()->format('d-m-Y') }}</div>

    <h2>Receivables (from clients)</h2>
    <table><thead><tr><th>Project</th><th class="r">Retention</th><th class="r">Receivable</th></tr></thead><tbody>
        @forelse($receivables as $r)<tr><td>{{ $r['name'] }}</td><td class="r">{{ $rs($r['retention']) }}</td><td class="r">{{ $rs($r['receivable']) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse
    </tbody></table>

    <h2>Payables (to vendors)</h2>
    <table><thead><tr><th>Vendor</th><th class="r">Amount</th></tr></thead><tbody>
        @forelse($payables as $r)<tr><td>{{ $r['name'] }}</td><td class="r">{{ $rs($r['amount']) }}</td></tr>@empty<tr><td colspan="2">—</td></tr>@endforelse
    </tbody></table>

    <h2>Worker Advances</h2>
    <table><thead><tr><th>Worker</th><th class="r">Amount</th></tr></thead><tbody>
        @forelse($advances as $r)<tr><td>{{ $r['name'] }}</td><td class="r">{{ $rs($r['amount']) }}</td></tr>@empty<tr><td colspan="2">—</td></tr>@endforelse
    </tbody></table>
</body>
</html>
