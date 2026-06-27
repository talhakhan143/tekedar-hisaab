<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $logoPath  = public_path('images/logo.jpeg');
        $address   = \App\Models\Setting::get('company_address');
        $phone     = \App\Models\Setting::get('company_phone');
        $size      = request('size') === 'pos' ? 'pos' : 'a4';   // default A4
        $qs        = fn ($s) => request()->fullUrlWithQuery(['size' => $s]);
    @endphp
    <title>{{ $docTitle ?? 'Voucher' }} · Ali Building Construction Group</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #9ca3af; color: #000; }
        .toolbar {
            position: sticky; top: 0; display: flex; gap: 8px; justify-content: center; align-items: center;
            padding: 12px; background: #111827; z-index: 10; flex-wrap: wrap;
            font-family: system-ui, sans-serif;
        }
        .toolbar button, .toolbar a {
            border: 0; border-radius: 6px; padding: 8px 16px; font-size: 13px; font-weight: 600;
            cursor: pointer; text-decoration: none;
        }
        .btn-print { background: #059669; color: #fff; }
        .btn-back  { background: #374151; color: #fff; }
        .seg { display: inline-flex; border: 1px solid #4b5563; border-radius: 6px; overflow: hidden; }
        .seg a { border-radius: 0; padding: 8px 14px; background: #1f2937; color: #9ca3af; font-weight: 600; }
        .seg a.on { background: #2563eb; color: #fff; }

        .sheet { background: #fff; margin: 16px auto; }

        /* shared content */
        .center { text-align: center; }
        .hr { border: 0; border-top: 1px dashed #000; margin: 8px 0; }
        .biz { font-weight: 800; text-transform: uppercase; }
        .biz-meta { font-size: 11px; color: #444; }
        .title { text-align: center; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .docmeta { text-align: center; color: #555; }
        .line { display: flex; justify-content: space-between; gap: 10px; }
        .line .v { text-align: right; font-weight: 700; }
        .muted { color: #555; }
        .item .nm { font-weight: 700; }
        .tot { display: flex; justify-content: space-between; }
        .tot.grand { font-weight: 800; border-top: 2px solid #000; padding-top: 5px; margin-top: 5px; }
        .words b { color: #000; }
        .notes { color: #333; }
        .pill { display: inline-block; border: 1px solid #000; padding: 1px 8px; font-weight: 700; }
        .sign { margin-top: 40px; }
        .foot { text-align: center; color: #555; }

        /* ---------- A4 ---------- */
        .sz-a4 { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 14px; line-height: 1.5; }
        .sz-a4 .sheet { width: 780px; max-width: 95%; min-height: 1080px; padding: 40px 48px; box-shadow: 0 2px 12px rgba(0,0,0,.25); display: flex; flex-direction: column; }
        .sz-a4 .tail { margin-top: auto; }
        .sz-a4 .logo { height: 96px; }
        .sz-a4 .biz { font-size: 24px; }
        .sz-a4 .title { font-size: 17px; margin: 18px 0 4px; color: #1e3a5f; }
        .sz-a4 .docmeta { font-size: 12px; margin-bottom: 16px; }
        .sz-a4 .hr { border-top: 1px solid #cbd5e1; }
        .sz-a4 .lead { border-bottom: 2px solid #1e3a5f; }
        .sz-a4 .line, .sz-a4 .tot { padding: 4px 0; }
        .sz-a4 .tot.grand { font-size: 18px; color: #1e3a5f; }
        .sz-a4 .sign { display: flex; justify-content: space-between; }
        .sz-a4 .sign .box { width: 42%; text-align: center; }
        .sz-a4 .sign .ln { border-top: 1px solid #555; padding-top: 6px; font-size: 12px; color: #555; }
        .sz-a4 .foot { margin-top: 28px; font-size: 11px; }

        /* ---------- POS 80mm ---------- */
        .sz-pos { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.45; }
        .sz-pos .sheet { width: 80mm; padding: 6mm 5mm; box-shadow: 0 2px 10px rgba(0,0,0,.3); }
        .sz-pos .logo { height: 56px; }
        .sz-pos .biz { font-size: 14px; margin-top: 3px; }
        .sz-pos .title { font-size: 13px; margin: 2px 0; }
        .sz-pos .docmeta { font-size: 10px; margin-bottom: 4px; }
        .sz-pos .biz-meta { font-size: 10px; }
        .sz-pos .tot.grand { font-size: 15px; }
        .sz-pos .sign { text-align: center; margin-top: 26px; }
        .sz-pos .sign .box .ln, .sz-pos .sign .ln { border-top: 1px solid #000; width: 70%; margin: 0 auto; padding-top: 3px; font-size: 10px; }
        .sz-pos .foot { margin-top: 10px; font-size: 9px; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; box-shadow: none; max-width: 100%; }
            .sz-a4 .sheet { width: 100%; min-height: 297mm; padding: 14mm 16mm; }
            .sz-a4 { font-size: 12px; }
            .sz-pos .sheet { width: 80mm; padding: 2mm 3mm; }
            /* margin:0 removes the browser's auto date/URL header & footer */
            @page { size: {{ $size === 'pos' ? '80mm auto' : 'A4' }}; margin: 0; }
        }
    </style>
</head>
<body class="sz-{{ $size }}">
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">🖨 Print</button>
        <span class="seg">
            <a href="{{ $qs('a4') }}" class="{{ $size === 'a4' ? 'on' : '' }}">A4</a>
            <a href="{{ $qs('pos') }}" class="{{ $size === 'pos' ? 'on' : '' }}">POS 80mm</a>
        </span>
        <a class="btn-back" href="{{ $backUrl ?? url()->previous() }}">← Back</a>
    </div>

    <div class="sheet">
        <div class="content">
            <div class="center {{ $size === 'a4' ? 'lead' : '' }}" style="{{ $size === 'a4' ? 'padding-bottom:12px' : '' }}">
                @if (file_exists($logoPath))
                    <img src="{{ asset('images/logo.jpeg') }}" alt="logo" class="logo">
                @endif
                <div class="biz">Ali Building Construction Group</div>
                @if ($address)<div class="biz-meta">{{ $address }}</div>@endif
                @if ($phone)<div class="biz-meta">Ph: {{ $phone }}</div>@endif
            </div>

            @if ($size === 'pos')<hr class="hr">@endif
            <div class="title">{{ $docTitle ?? 'Voucher' }}</div>
            <div class="docmeta">{{ $docNo ?? '—' }} &nbsp;|&nbsp; {{ $docDate ?? now()->format('d-m-Y') }}</div>
            <hr class="hr">

            {{ $slot }}
        </div>

        <div class="tail">
            <hr class="hr">
            @if ($size === 'a4')
                <div class="sign">
                    <div class="box"><div class="ln">Receiver's Signature</div></div>
                    <div class="box"><div class="ln">For Ali Building Construction Group</div></div>
                </div>
            @else
                <div class="sign"><div class="ln">Signature</div></div>
            @endif
            <div class="foot">
                {{ now()->format('d-m-Y H:i') }} · Shukriya<br>
                Software Developed by Talha Khan · WhatsApp 0336-8469404
            </div>
        </div>
    </div>
</body>
</html>
