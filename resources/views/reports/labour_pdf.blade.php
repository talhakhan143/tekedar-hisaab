<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; color: #1f2937; }
    h1 { font-size: 18px; margin: 0 0 2px; }
    h2 { font-size: 13px; margin: 16px 0 2px; }
    .muted { color: #6b7280; font-size: 11px; }
    .worker { page-break-inside: avoid; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th, td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
    td.r, th.r { text-align: right; }
    tr.total td { font-weight: bold; background: #f9fafb; }
    tr.grand td { font-weight: bold; background: #f3f4f6; }
    .dena { color: #dc2626; }
    .adv { color: #d97706; }
    .tag { color: #6b7280; font-size: 9px; }
</style>
</head>
<body>
    @php
        $rs   = fn ($p) => 'Rs ' . number_format(\App\Support\Money::toRupees($p), 2);
        $days = fn ($d) => rtrim(rtrim(number_format((float) $d, 1), '0'), '.');
    @endphp
    @include('reports._letterhead')
    <div class="muted">Mazdoor ka hisaab &middot; {{ $scopeLabel }} &middot; generated {{ now()->format('d-m-Y') }}</div>

    <table style="margin-top:10px;">
        <thead><tr><th>Banta tha</th><th>Diya</th><th>Peshgi</th><th>Baqi</th></tr></thead>
        <tbody>
            <tr>
                <td>{{ $rs($grand['earned']) }}</td>
                <td>{{ $rs($grand['paid']) }}</td>
                <td>{{ $rs($grand['advnet']) }}</td>
                <td class="{{ $grand['baqi'] < 0 ? 'adv' : 'dena' }}">{{ $rs($grand['baqi']) }}</td>
            </tr>
        </tbody>
    </table>

    @forelse($report as $r)
        <div class="worker">
            <h2>{{ $r['worker']->name }} <span class="muted">#{{ $r['worker']->id }} &middot; {{ ucfirst($r['worker']->role) }}</span></h2>
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th class="r">Din</th>
                        <th class="r">Banta tha</th>
                        <th class="r">Diya</th>
                        <th class="r">Peshgi</th>
                        <th class="r">Baqi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($r['lines'] as $l)
                        <tr>
                            <td>{{ $l['project_id'] === 0 ? 'Bina project' : $l['project'] }}</td>
                            <td class="r">{{ $days($l['days']) }}</td>
                            <td class="r">{{ $rs($l['earned']) }}</td>
                            <td class="r">{{ $rs($l['paid']) }}</td>
                            <td class="r">{{ $rs($l['advnet']) }}</td>
                            <td class="r">
                                @if($l['baqi'] > 0)
                                    <span class="dena">{{ $rs($l['baqi']) }}</span> <span class="tag">dena</span>
                                @elseif($l['baqi'] < 0)
                                    <span class="adv">{{ $rs(-$l['baqi']) }}</span> <span class="tag">advance</span>
                                @else
                                    barabar
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Is mazdoor ka koi hisaab nahi.</td></tr>
                    @endforelse
                    @if(count($r['lines']))
                        <tr class="total">
                            <td>TOTAL</td>
                            <td class="r">{{ $days($r['total']['days']) }}</td>
                            <td class="r">{{ $rs($r['total']['earned']) }}</td>
                            <td class="r">{{ $rs($r['total']['paid']) }}</td>
                            <td class="r">{{ $rs($r['total']['advnet']) }}</td>
                            <td class="r">
                                @if($r['total']['baqi'] > 0)
                                    <span class="dena">{{ $rs($r['total']['baqi']) }}</span> <span class="tag">dena</span>
                                @elseif($r['total']['baqi'] < 0)
                                    <span class="adv">{{ $rs(-$r['total']['baqi']) }}</span> <span class="tag">advance</span>
                                @else
                                    barabar
                                @endif
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @empty
        <p class="muted" style="margin-top:14px;">Koi mazdoor nahi mila. Filter badal kar dekho.</p>
    @endforelse

    @if(count($report) > 1)
        <h2>Sab mila kar ({{ count($report) }} bande)</h2>
        <table>
            <thead>
                <tr>
                    <th>Kul</th>
                    <th class="r">Din</th>
                    <th class="r">Banta tha</th>
                    <th class="r">Diya</th>
                    <th class="r">Peshgi</th>
                    <th class="r">Baqi</th>
                </tr>
            </thead>
            <tbody>
                <tr class="grand">
                    <td>GRAND TOTAL</td>
                    <td class="r">{{ $days($grand['days']) }}</td>
                    <td class="r">{{ $rs($grand['earned']) }}</td>
                    <td class="r">{{ $rs($grand['paid']) }}</td>
                    <td class="r">{{ $rs($grand['advnet']) }}</td>
                    <td class="r {{ $grand['baqi'] < 0 ? 'adv' : 'dena' }}">{{ $rs($grand['baqi']) }}</td>
                </tr>
                @if($grand['dena'] && $grand['advance'])
                    <tr>
                        <td colspan="5" class="muted">Jin ko dena hai aur jin se lena hai, alag alag (aapas me cancel nahi hota)</td>
                        <td class="r">
                            <span class="dena">{{ $rs($grand['dena']) }}</span> <span class="tag">dena</span><br>
                            <span class="adv">{{ $rs($grand['advance']) }}</span> <span class="tag">advance</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif
</body>
</html>
