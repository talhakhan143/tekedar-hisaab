<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; color: #1f2937; }
    h1 { font-size: 18px; margin: 0 0 2px; }
    .muted { color: #6b7280; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
    td.r, th.r { text-align: right; }
    tr.total td { font-weight: bold; background: #f9fafb; }
    .neg { color: #dc2626; }
</style>
</head>
<body>
    @php $rs = fn($p) => 'Rs ' . number_format(\App\Support\Money::toRupees($p), 2); @endphp
    @include('reports._letterhead')
    <div class="muted">Monthly Profit Report — {{ $carbon->format('F Y') }} · generated {{ now()->format('d-m-Y') }}</div>

    <table>
        <thead><tr><th>Project</th><th class="r">Received</th><th class="r">Spent</th><th class="r">Profit</th></tr></thead>
        <tbody>
            @foreach($rows as $r)
                <tr><td>{{ $r['name'] }}</td><td class="r">{{ $rs($r['received']) }}</td><td class="r">{{ $rs($r['spent']) }}</td><td class="r {{ $r['profit']<0?'neg':'' }}">{{ $rs($r['profit']) }}</td></tr>
            @endforeach
            <tr><td>General Overheads</td><td class="r">—</td><td class="r">{{ $rs($overheads) }}</td><td class="r">—</td></tr>
            <tr class="total"><td>NET PROFIT</td><td class="r">{{ $rs($totalReceived) }}</td><td class="r">{{ $rs($totalSpent + $overheads) }}</td><td class="r {{ $netProfit<0?'neg':'' }}">{{ $rs($netProfit) }}</td></tr>
        </tbody>
    </table>
</body>
</html>
