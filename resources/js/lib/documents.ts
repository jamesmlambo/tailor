import type {
    DiscountType,
    DocumentType,
    LineItem,
} from '@/types/documents';

export const DOCUMENT_TYPES: { value: DocumentType; label: string }[] = [
    { value: 'invoice', label: 'Invoice' },
    { value: 'quotation', label: 'Quotation' },
    { value: 'receipt', label: 'Receipt' },
];

export const TYPE_LABELS: Record<DocumentType, string> = {
    invoice: 'Invoice',
    quotation: 'Quotation',
    receipt: 'Receipt',
};

export const TYPE_PLURALS: Record<DocumentType, string> = {
    invoice: 'Invoices',
    quotation: 'Quotations',
    receipt: 'Receipts',
};

export const TYPE_PATHS: Record<DocumentType, string> = {
    invoice: '/invoices',
    quotation: '/quotations',
    receipt: '/receipts',
};

export const STATUS_OPTIONS: Record<DocumentType, { value: string; label: string }[]> = {
    invoice: [
        { value: 'draft', label: 'Draft' },
        { value: 'sent', label: 'Sent' },
        { value: 'paid', label: 'Paid' },
        { value: 'overdue', label: 'Overdue' },
    ],
    quotation: [
        { value: 'draft', label: 'Draft' },
        { value: 'sent', label: 'Sent' },
        { value: 'accepted', label: 'Accepted' },
        { value: 'declined', label: 'Declined' },
        { value: 'expired', label: 'Expired' },
    ],
    receipt: [
        { value: 'issued', label: 'Issued' },
        { value: 'void', label: 'Void' },
    ],
};

export const DEFAULT_STATUS: Record<DocumentType, string> = {
    invoice: 'draft',
    quotation: 'draft',
    receipt: 'issued',
};

export function today(): string {
    return new Date().toISOString().slice(0, 10);
}

export function addDays(date: string, days: number): string {
    const value = new Date(date + 'T00:00:00');
    value.setDate(value.getDate() + days);

    return value.toISOString().slice(0, 10);
}

export function formatMoney(
    amount: number | string | null | undefined,
    currency: string,
): string {
    const value = Number(amount ?? 0);

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: currency || 'USD',
            currencyDisplay: 'narrowSymbol',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number.isFinite(value) ? value : 0);
    } catch {
        return `${currency} ${(Number.isFinite(value) ? value : 0).toFixed(2)}`;
    }
}

export function formatNumber(
    value: number | string | null | undefined,
    maximumFractionDigits = 2,
): string {
    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits,
    }).format(Number(value ?? 0));
}

export function formatDate(
    value: string | null | undefined,
    options: Intl.DateTimeFormatOptions = {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    },
): string {
    if (!value) {
        return '—';
    }

    const date = new Date(
        value.includes('T') ? value : `${value}T00:00:00`,
    );

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, options).format(date);
}

export type CalculatedTotals = {
    lines: LineItem[];
    subtotal: number;
    discountAmount: number;
    taxAmount: number;
    total: number;
};

export function calculateTotals(
    items: LineItem[],
    discountType: DiscountType | null | undefined,
    discountValue: number | string | null | undefined,
): CalculatedTotals {
    const lines = (items ?? []).map((item) => {
        const quantity = Number(item.quantity ?? 0);
        const unitPrice = Number(item.unit_price ?? 0);
        const taxRate = Number(item.tax_rate ?? 0);

        return {
            ...item,
            description: item.description ?? '',
            quantity,
            unit_price: unitPrice,
            tax_rate: taxRate,
            line_total: round(quantity * unitPrice),
        };
    });

    const subtotal = round(
        lines.reduce((sum, item) => sum + Number(item.line_total ?? 0), 0),
    );

    const rawTax = round(
        lines.reduce(
            (sum, item) =>
                sum +
                Number(item.line_total ?? 0) * (Number(item.tax_rate ?? 0) / 100),
            0,
        ),
    );

    const value = Math.max(0, Number(discountValue ?? 0));
    const discountAmount =
        discountType === 'percent'
            ? round(subtotal * (Math.min(value, 100) / 100))
            : round(Math.min(value, subtotal));

    const discountRatio = subtotal > 0 ? discountAmount / subtotal : 0;
    const taxAmount = round(rawTax * (1 - discountRatio));
    const total = round(subtotal - discountAmount + taxAmount);

    return {
        lines,
        subtotal,
        discountAmount,
        taxAmount,
        total,
    };
}

export function round(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

export function statusTone(status: string): string {
    switch (status) {
        case 'paid':
        case 'accepted':
        case 'issued':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300';
        case 'sent':
            return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300';
        case 'overdue':
        case 'declined':
        case 'void':
            return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300';
        case 'expired':
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300';
        default:
            return 'border-neutral-200 bg-neutral-100 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
    }
}

export function dueLabel(type: DocumentType): string {
    return type === 'quotation'
        ? 'Valid until'
        : type === 'receipt'
          ? 'Payment date'
          : 'Due date';
}
