@php
    $typeLabel = \App\Models\Document::TYPES[$document->type] ?? 'Document';
    $statusLabel = ucfirst($document->status);
    $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('M j, Y') : '—';
    $money = fn ($amount) => \App\Support\Currency::format($amount, $document->currency);
    $businessLines = array_filter([
        $business['address'] ?? null,
        trim(implode(', ', array_filter([$business['city'] ?? null, $business['country'] ?? null]))),
        $business['phone'] ?? null,
        $business['email'] ?? null,
        $business['website'] ?? null,
        ! empty($business['tax_number']) ? 'Tax / Reg: '.$business['tax_number'] : null,
    ]);
    $notes = $document->notes ?: ($business['notes'] ?? null);
    $terms = $document->terms ?: ($business['payment_terms'] ?? null);
    $hasLogo = ! empty($business['logo_url']);
    $isBold = $theme === 'bold';
    $isClassic = $theme === 'classic';
    $isMinimal = $theme === 'minimal';
    $soft = match ($theme) {
        'classic' => '#f8f5ef',
        'bold' => '#fff7ed',
        'minimal' => '#fafaf9',
        default => '#eef2ff',
    };
    $muted = $isClassic ? '#475569' : '#64748b';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $typeLabel }} {{ $document->number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: {{ $isClassic ? "'DejaVu Serif', serif" : "'DejaVu Sans', sans-serif" }};
            font-size: 11px;
            line-height: 1.55;
            color: #111827;
        }
        .page { padding: {{ $isBold ? '0' : '46px 48px 56px' }}; }
        .inner { padding: {{ $isBold ? '38px 48px 8px' : '0' }}; }
        .accent-bar { height: 7px; background: {{ $accent }}; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .brand-name { font-size: {{ $isBold ? '26px' : '22px' }}; font-weight: bold; letter-spacing: -.4px; margin: 0 0 6px; }
        .brand-details { color: {{ $muted }}; font-size: 10px; line-height: 1.7; }
        .logo { max-height: 64px; max-width: 190px; margin-bottom: 12px; }
        .logo-fallback {
            display: inline-block; width: 46px; height: 46px; line-height: 46px; text-align: center;
            background: {{ $accent }}; color: #ffffff; font-size: 17px; font-weight: bold;
            border-radius: 8px; margin-bottom: 10px;
        }
        .doc-type {
            margin: 0; font-size: {{ $isBold ? '30px' : '25px' }};
            letter-spacing: {{ $isMinimal ? '4px' : '1.5px' }};
            text-transform: uppercase; font-weight: bold;
            color: {{ $isBold ? '#ffffff' : $accent }};
        }
        .doc-number { font-size: 12px; color: {{ $isBold ? '#e5e7eb' : $muted }}; margin-top: 4px; }
        .badge {
            display: inline-block; margin-top: 10px; padding: 4px 10px; border-radius: 20px;
            font-size: 9px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;
            background: {{ $soft }}; color: {{ $isBold ? '#111827' : $accent }};
            border: 1px solid {{ $accent }}33;
        }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 34px; }
        .meta-table td { vertical-align: top; width: 50%; }
        .meta-label {
            font-size: 8px; letter-spacing: 1.6px; text-transform: uppercase;
            color: {{ $muted }}; font-weight: bold; margin-bottom: 6px;
        }
        .client-name { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .client-details { color: {{ $muted }}; line-height: 1.65; }
        .date-row { margin-bottom: 6px; }
        .date-row span:first-child { display: inline-block; width: 92px; color: {{ $muted }}; }
        .date-row span:last-child { font-weight: bold; }
        .items { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .items th {
            text-align: left; padding: 10px 8px; font-size: 8px; letter-spacing: 1.2px;
            text-transform: uppercase; background: {{ $isMinimal ? '#ffffff' : $soft }};
            color: {{ $isMinimal ? '#111827' : ($isBold ? '#9a3412' : $accent) }};
            border-top: 1px solid {{ $accent }}; border-bottom: 1px solid {{ $accent }};
        }
        .items td { padding: 11px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .items .right { text-align: right; }
        .items .num { width: 22px; color: {{ $muted }}; }
        .item-desc { font-weight: bold; }
        .item-sub { color: {{ $muted }}; font-size: 9px; }
        .totals-wrap { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .totals { width: 270px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 7px 10px; }
        .totals .label { color: {{ $muted }}; }
        .totals .value { text-align: right; font-weight: bold; }
        .totals .grand td {
            background: {{ $accent }}; color: #ffffff; font-size: 13px; font-weight: bold;
            padding: 11px 10px;
        }
        .totals .grand .label { color: #ffffff; }
        .totals .grand .value { font-size: 14px; }
        .totals .balance td { border-top: 1px dashed {{ $muted }}; font-weight: bold; }
        .section-table { width: 100%; border-collapse: collapse; margin-top: 34px; }
        .section-table td { vertical-align: top; width: 50%; padding-right: 22px; }
        .section-title {
            font-size: 8px; letter-spacing: 1.5px; text-transform: uppercase;
            color: {{ $muted }}; font-weight: bold; margin-bottom: 6px;
        }
        .section-text { color: {{ $isMinimal ? '#111827' : '#374151' }}; font-size: 10px; line-height: 1.7; }
        .bank-box {
            margin-top: 26px; padding: 14px 16px; background: {{ $soft }};
            border-left: 3px solid {{ $accent }};
        }
        .bank-box .section-text { font-family: 'DejaVu Sans', sans-serif; }
        .footer {
            margin-top: 44px; padding-top: 12px; border-top: 1px solid #e5e7eb;
            text-align: center; color: {{ $muted }}; font-size: 9px;
        }
        .bold-top { background: #111827; padding: 34px 48px; color: #ffffff; }
        .bold-top .brand-details { color: #cbd5e1; }
        .bold-top .logo-fallback { background: {{ $accent }}; }
    </style>
</head>
<body>
<div class="page">
    @if($isBold)
        <div class="bold-top">
            <table class="header-table">
                <tr>
                    <td style="width: 60%;">
                        @if($hasLogo)
                            <img src="{{ $business['logo_url'] }}" class="logo" alt="Logo">
                        @else
                            <div class="logo-fallback">{{ $business['initials'] ?? 'B' }}</div>
                        @endif
                        <div class="brand-name">{{ $business['name'] ?: 'Your business' }}</div>
                        <div class="brand-details">
                            @foreach($businessLines as $line)
                                {!! nl2br(e($line)) !!}@if(! $loop->last)<br>@endif
                            @endforeach
                        </div>
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <div class="doc-type">{{ $typeLabel }}</div>
                        <div class="doc-number">#{{ $document->number }}</div>
                        <div class="badge">{{ $statusLabel }}</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="inner">
    @else
        @if($theme === 'modern')
            <div class="accent-bar"></div>
        @endif
        <div class="{{ $theme === 'modern' ? 'inner' : '' }}">
            <table class="header-table">
                <tr>
                    <td style="width: 60%;">
                        @if($hasLogo)
                            <img src="{{ $business['logo_url'] }}" class="logo" alt="Logo">
                        @else
                            <div class="logo-fallback">{{ $business['initials'] ?? 'B' }}</div>
                        @endif
                        <div class="brand-name" style="color: {{ $isMinimal ? '#111827' : '#111827' }};">{{ $business['name'] ?: 'Your business' }}</div>
                        <div class="brand-details">
                            @foreach($businessLines as $line)
                                {!! nl2br(e($line)) !!}@if(! $loop->last)<br>@endif
                            @endforeach
                        </div>
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <div class="doc-type">{{ $typeLabel }}</div>
                        <div class="doc-number">#{{ $document->number }}</div>
                        <div class="badge">{{ $statusLabel }}</div>
                    </td>
                </tr>
            </table>
    @endif

    <table class="meta-table">
        <tr>
            <td>
                <div class="meta-label">{{ $document->type === 'receipt' ? 'Received from' : ($document->type === 'quotation' ? 'Prepared for' : 'Billed to') }}</div>
                <div class="client-name">{{ $document->client_name }}</div>
                <div class="client-details">
                    @if($document->client_address)
                        {!! nl2br(e($document->client_address)) !!}<br>
                    @endif
                    @if($document->client_email){{ $document->client_email }}<br>@endif
                    @if($document->client_phone){{ $document->client_phone }}@endif
                </div>
            </td>
            <td>
                <div class="date-row"><span>{{ $document->type === 'receipt' ? 'Receipt date' : 'Issue date' }}</span><span>{{ $formatDate($document->issue_date) }}</span></div>
                <div class="date-row"><span>{{ $document->dueLabel() }}</span><span>{{ $formatDate($document->due_date) }}</span></div>
                <div class="date-row"><span>Currency</span><span>{{ $document->currency }}</span></div>
                <div class="date-row"><span>{{ $typeLabel }} no.</span><span>{{ $document->number }}</span></div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                @if($hasTax)
                    <th class="right">Tax</th>
                @endif
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td class="item-desc">{{ $item->description }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ','), '0'), '.') }}</td>
                    <td class="right">{{ $money($item->unit_price) }}</td>
                    @if($hasTax)
                        <td class="right">{{ rtrim(rtrim(number_format((float) $item->tax_rate, 2, '.', ''), '0'), '.') }}%</td>
                    @endif
                    <td class="right">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-wrap">
        <tr>
            <td>
                <table class="totals">
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="value">{{ $money($document->subtotal) }}</td>
                    </tr>
                    @if((float) $document->discount_amount > 0)
                        <tr>
                            <td class="label">Discount{{ $document->discount_type === 'percent' ? ' ('.$document->discount_value.'%)' : '' }}</td>
                            <td class="value">-{{ $money($document->discount_amount) }}</td>
                        </tr>
                    @endif
                    @if((float) $document->tax_amount > 0)
                        <tr>
                            <td class="label">Tax</td>
                            <td class="value">{{ $money($document->tax_amount) }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td class="label">{{ $document->type === 'receipt' ? 'Amount received' : 'Total' }}</td>
                        <td class="value">{{ $money($document->total) }}</td>
                    </tr>
                    @if((float) $document->amount_paid > 0 && (float) $document->amount_paid < (float) $document->total)
                        <tr>
                            <td class="label">Amount paid</td>
                            <td class="value">{{ $money($document->amount_paid) }}</td>
                        </tr>
                        <tr class="balance">
                            <td class="label">Balance due</td>
                            <td class="value">{{ $money($balance) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="section-table">
        <tr>
            @if($notes)
                <td>
                    <div class="section-title">Notes</div>
                    <div class="section-text">{!! nl2br(e($notes)) !!}</div>
                </td>
            @endif
            @if($terms)
                <td>
                    <div class="section-title">Terms &amp; payment</div>
                    <div class="section-text">{!! nl2br(e($terms)) !!}</div>
                </td>
            @endif
        </tr>
    </table>

    @if(! empty($business['bank_details']))
        <div class="bank-box">
            <div class="section-title">Payment details</div>
            <div class="section-text">{!! nl2br(e($business['bank_details'])) !!}</div>
        </div>
    @endif

    <div class="footer">
        {{ $typeLabel }} {{ $document->number }} &middot; {{ $business['name'] ?: 'Your business' }}
        @if($business['email'] ?? null) &middot; {{ $business['email'] }} @endif
        @if($business['phone'] ?? null) &middot; {{ $business['phone'] }} @endif
        <br>Generated with Tailor Studio
    </div>

    @if($isBold)
        </div>
    @else
        </div>
    @endif
</div>
</body>
</html>
