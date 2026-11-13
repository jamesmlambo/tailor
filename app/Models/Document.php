<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'type', 'number', 'status', 'theme', 'currency', 'issue_date', 'due_date',
    'client_name', 'client_email', 'client_phone', 'client_address', 'subtotal',
    'discount_type', 'discount_value', 'discount_amount', 'tax_amount', 'total',
    'amount_paid', 'notes', 'terms', 'business_snapshot', 'share_token',
    'sent_at', 'paid_at',
])]
class Document extends Model
{
    public const TYPES = [
        'invoice' => 'Invoice',
        'quotation' => 'Quotation',
        'receipt' => 'Receipt',
    ];

    public const TYPE_PLURALS = [
        'invoice' => 'Invoices',
        'quotation' => 'Quotations',
        'receipt' => 'Receipts',
    ];

    public const STATUSES = [
        'invoice' => [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
        ],
        'quotation' => [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'expired' => 'Expired',
        ],
        'receipt' => [
            'issued' => 'Issued',
            'void' => 'Void',
        ],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'business_snapshot' => 'array',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            $document->share_token ??= Str::random(40);
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('position');
    }

    /**
     * @return array<string, mixed>
     */
    public function businessDetails(): array
    {
        $snapshot = $this->business_snapshot;

        if (! is_array($snapshot) || empty($snapshot['name'])) {
            return BusinessProfile::current()->snapshot();
        }

        if (empty($snapshot['logo_url']) && ! empty($snapshot['logo_path'])) {
            $snapshot['logo_url'] = Storage::disk('public')->url($snapshot['logo_path']);
        }

        return $snapshot;
    }

    public function isTaxed(): bool
    {
        return $this->items->contains(fn (DocumentItem $item): bool => (float) $item->tax_rate > 0);
    }

    public function dueLabel(): string
    {
        return match ($this->type) {
            'quotation' => 'Valid until',
            'receipt' => 'Payment date',
            default => 'Due date',
        };
    }

    public function shareUrl(): string
    {
        return route('documents.public', ['token' => $this->share_token]);
    }

    public function pdfUrl(): string
    {
        return route('documents.pdf', $this);
    }

    public function publicPdfUrl(): string
    {
        return route('documents.public.pdf', ['token' => $this->share_token]);
    }

    public function whatsappUrl(): string
    {
        $business = $this->businessDetails();
        $message = sprintf(
            'Hello %s, here is %s %s from %s for %s. View it here: %s',
            $this->client_name,
            Str::lower(self::TYPES[$this->type] ?? 'document'),
            $this->number,
            $business['name'] ?: 'us',
            \App\Support\Currency::format($this->total, $this->currency),
            $this->shareUrl(),
        );

        $phone = preg_replace('/\D+/', '', (string) $this->client_phone) ?? '';
        $recipient = strlen($phone) >= 8 ? $phone : '';

        return 'https://wa.me/'.$recipient.'?text='.rawurlencode($message);
    }

    public function mailtoUrl(): string
    {
        $business = $this->businessDetails();
        $subject = sprintf('%s %s from %s', self::TYPES[$this->type] ?? 'Document', $this->number, $business['name'] ?: 'our business');
        $body = sprintf(
            "Hello %s,\n\nPlease find %s %s for %s attached below.\n\nView or download it here: %s\n\nThank you,\n%s",
            $this->client_name,
            Str::lower(self::TYPES[$this->type] ?? 'document'),
            $this->number,
            \App\Support\Currency::format($this->total, $this->currency),
            $this->shareUrl(),
            $business['name'] ?: '',
        );

        return 'mailto:'.rawurlencode((string) $this->client_email).'?subject='.rawurlencode($subject).'&body='.rawurlencode($body);
    }

    /**
     * @return array<int, array{description: string, quantity: float, unit_price: float, tax_rate: float, line_total: float}>
     */
    public static function calculateLines(array $items): array
    {
        return collect($items)->values()->map(function (array $item): array {
            $quantity = round((float) ($item['quantity'] ?? 0), 3);
            $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);
            $taxRate = round((float) ($item['tax_rate'] ?? 0), 2);

            return [
                'description' => trim((string) ($item['description'] ?? '')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, discount_amount: float, tax_amount: float, total: float, lines: array<int, array<string, mixed>>}
     */
    public static function calculateTotals(array $items, string $discountType = 'fixed', float|int|string $discountValue = 0): array
    {
        $lines = self::calculateLines($items);
        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
        $rawTax = 0.0;

        foreach ($lines as $line) {
            $rawTax += $line['line_total'] * ($line['tax_rate'] / 100);
        }

        $rawTax = round($rawTax, 2);
        $discountValue = max(0, (float) $discountValue);

        $discountAmount = $discountType === 'percent'
            ? round($subtotal * (min($discountValue, 100) / 100), 2)
            : round(min($discountValue, $subtotal), 2);

        // Keep the tax proportional to the amount actually being charged.
        $discountRatio = $subtotal > 0 ? $discountAmount / $subtotal : 0;
        $taxAmount = round($rawTax * (1 - $discountRatio), 2);
        $total = round($subtotal - $discountAmount + $taxAmount, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'lines' => $lines,
        ];
    }

    public static function nextNumber(string $type, ?string $issueDate = null, ?string $prefix = null): string
    {
        $prefix = $prefix ?: match ($type) {
            'quotation' => 'QUO',
            'receipt' => 'RCP',
            default => 'INV',
        };

        $year = $issueDate ? date('Y', strtotime($issueDate)) : now()->format('Y');
        $pattern = $prefix.'-'.$year.'-';

        $last = static::query()
            ->where('type', $type)
            ->where('number', 'like', $pattern.'%')
            ->orderByDesc('id')
            ->value('number');

        $sequence = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        do {
            $number = sprintf('%s%04d', $pattern, $sequence);
            $sequence++;
        } while (static::query()->where('number', $number)->exists());

        return $number;
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(string $type): array
    {
        return self::STATUSES[$type] ?? ['draft' => 'Draft'];
    }

    public static function defaultStatus(string $type): string
    {
        return $type === 'receipt' ? 'issued' : 'draft';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function themeOptions(): array
    {
        return BusinessProfile::THEMES;
    }
}
