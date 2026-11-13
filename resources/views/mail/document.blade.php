@php
    $money = fn ($amount) => \App\Support\Currency::format($amount, $document->currency);
    $typeLabel = \App\Models\Document::TYPES[$document->type] ?? 'Document';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $typeLabel }} {{ $document->number }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#4f46e5;padding:24px 28px;color:#ffffff;font-size:18px;font-weight:bold;">
                            {{ $business['name'] ?: 'Your business' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 14px;font-size:14px;">Hello {{ $document->client_name }},</p>
                            <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#374151;">
                                Please find {{ $typeLabel }} <strong>{{ $document->number }}</strong>
                                for <strong>{{ $money($document->total) }}</strong> attached to this email.
                            </p>
                            <p style="margin:0 0 22px;">
                                <a href="{{ $shareUrl }}" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;padding:11px 18px;border-radius:8px;font-size:13px;font-weight:bold;">
                                    View {{ $typeLabel }}
                                </a>
                            </p>
                            <p style="margin:0;font-size:12px;color:#6b7280;line-height:1.7;">
                                You can also download a PDF copy from the link above.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;">
                            {{ $business['name'] ?: 'Your business' }}
                            @if($business['email'] ?? null) &middot; {{ $business['email'] }} @endif
                            @if($business['phone'] ?? null) &middot; {{ $business['phone'] }} @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
