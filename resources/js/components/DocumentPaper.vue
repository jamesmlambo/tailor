<script setup lang="ts">
import { computed } from 'vue';
import type { DocumentPaperData } from '@/types';
import {
    calculateTotals,
    dueLabel,
    formatDate,
    formatMoney,
    formatNumber,
} from '@/lib/documents';

const props = defineProps<{
    document: DocumentPaperData;
}>();

const theme = computed(() => props.document.theme ?? 'modern');
const business = computed(() => props.document.business);
const totals = computed(() =>
    calculateTotals(
        props.document.items ?? [],
        props.document.discount_type,
        props.document.discount_value,
    ),
);
const hasTax = computed(() =>
    totals.value.lines.some((item) => Number(item.tax_rate) > 0),
);
const amountPaid = computed(() => Number(props.document.amount_paid ?? 0));
const balance = computed(() =>
    Math.max(0, totals.value.total - amountPaid.value),
);
const showBalance = computed(
    () =>
        amountPaid.value > 0 &&
        amountPaid.value < totals.value.total &&
        props.document.type !== 'receipt',
);
const notes = computed(
    () => props.document.notes || business.value.notes || '',
);
const terms = computed(
    () => props.document.terms || business.value.payment_terms || '',
);
const isBold = computed(() => theme.value === 'bold');

const wrapperClass = computed(() => [
    'document-paper w-full bg-white text-neutral-900',
    theme.value === 'classic' ? 'font-serif' : 'font-sans',
]);

const headerClass = computed(() => {
    switch (theme.value) {
        case 'classic':
            return 'border-b-4 border-double border-[#1e3a5f] px-10 pt-10 pb-7';
        case 'minimal':
            return 'border-b border-neutral-900 px-10 pt-11 pb-7';
        case 'bold':
            return 'bg-neutral-900 px-10 pt-9 pb-8 text-white';
        default:
            return 'px-10 pt-9 pb-7';
    }
});

const docTypeClass = computed(() => {
    switch (theme.value) {
        case 'classic':
            return 'text-[#1e3a5f]';
        case 'minimal':
            return 'text-neutral-900 tracking-[0.35em]';
        case 'bold':
            return 'text-orange-400';
        default:
            return 'text-indigo-600';
    }
});

const badgeClass = computed(() => {
    switch (theme.value) {
        case 'bold':
            return 'border-white/20 bg-white/10 text-white';
        case 'classic':
            return 'border-[#1e3a5f]/20 bg-[#f8f5ef] text-[#1e3a5f]';
        case 'minimal':
            return 'border-neutral-300 bg-neutral-50 text-neutral-700';
        default:
            return 'border-indigo-200 bg-indigo-50 text-indigo-700';
    }
});

const fallbackClass = computed(() => {
    switch (theme.value) {
        case 'classic':
            return 'bg-[#1e3a5f]';
        case 'minimal':
            return 'bg-neutral-900';
        case 'bold':
            return 'bg-orange-500';
        default:
            return 'bg-indigo-600';
    }
});

const tableHeadClass = computed(() => {
    switch (theme.value) {
        case 'classic':
            return 'border-y border-[#1e3a5f] bg-[#f8f5ef] text-[#1e3a5f]';
        case 'minimal':
            return 'border-y border-neutral-900 bg-white text-neutral-900';
        case 'bold':
            return 'border-y border-orange-500 bg-orange-50 text-orange-900';
        default:
            return 'border-y border-indigo-600 bg-indigo-50 text-indigo-900';
    }
});

const grandClass = computed(() => {
    switch (theme.value) {
        case 'classic':
            return 'border-y-2 border-double border-[#1e3a5f] text-[#1e3a5f]';
        case 'minimal':
            return 'bg-neutral-900 text-white';
        case 'bold':
            return 'bg-orange-500 text-white';
        default:
            return 'bg-indigo-600 text-white';
    }
});

const mutedText = computed(() => 'text-neutral-500');
const headerMutedText = computed(() =>
    isBold.value ? 'text-neutral-300' : 'text-neutral-500',
);

function businessLines(): string[] {
    const b = business.value;

    return [
        b.address,
        [b.city, b.country].filter(Boolean).join(', '),
        b.phone,
        b.email,
        b.website,
        b.tax_number ? `Tax / Reg: ${b.tax_number}` : null,
    ].filter((line): line is string => Boolean(line && String(line).trim()));
}
</script>

<template>
    <div :class="wrapperClass">
        <div v-if="theme === 'modern'" class="h-1.5 bg-indigo-600"></div>

        <header :class="headerClass">
            <div class="flex items-start justify-between gap-8">
                <div class="min-w-0">
                    <img
                        v-if="business.logo_url"
                        :src="business.logo_url"
                        alt="Business logo"
                        class="mb-3 max-h-16 max-w-[190px] object-contain"
                    />
                    <div
                        v-else
                        class="mb-3 flex size-12 items-center justify-center rounded-lg text-lg font-bold text-white"
                        :class="fallbackClass"
                    >
                        {{ business.initials || 'B' }}
                    </div>

                    <h1
                        class="text-2xl leading-tight font-bold tracking-tight"
                        :class="isBold ? 'text-white' : 'text-neutral-900'"
                    >
                        {{ business.name || 'Your business' }}
                    </h1>
                    <div
                        class="mt-2 space-y-0.5 text-[11px] leading-relaxed"
                        :class="headerMutedText"
                    >
                        <p v-for="line in businessLines()" :key="line">
                            {{ line }}
                        </p>
                    </div>
                </div>

                <div class="shrink-0 text-right">
                    <p
                        class="text-2xl font-bold uppercase"
                        :class="[docTypeClass, theme === 'minimal' ? 'text-[18px]' : '']"
                    >
                        {{ document.type === 'invoice' ? 'Invoice' : document.type === 'quotation' ? 'Quotation' : 'Receipt' }}
                    </p>
                    <p
                        class="mt-1 text-xs"
                        :class="isBold ? 'text-neutral-300' : 'text-neutral-500'"
                    >
                        #{{ document.number || 'DRAFT' }}
                    </p>
                    <span
                        class="mt-3 inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                        :class="badgeClass"
                    >
                        {{ document.status || 'draft' }}
                    </span>
                </div>
            </div>
        </header>

        <div
            class="px-10 pb-10"
            :class="isBold ? 'pt-8' : 'pt-7'"
        >
            <div class="flex items-start justify-between gap-8">
                <div>
                    <p
                        class="text-[10px] font-semibold tracking-[0.16em] uppercase"
                        :class="mutedText"
                    >
                        {{ document.type === 'receipt' ? 'Received from' : document.type === 'quotation' ? 'Prepared for' : 'Billed to' }}
                    </p>
                    <p class="mt-1.5 text-sm font-semibold">
                        {{ document.client_name || 'Client name' }}
                    </p>
                    <div
                        class="mt-1 space-y-0.5 text-xs leading-relaxed text-neutral-500"
                    >
                        <p v-if="document.client_address" class="whitespace-pre-line">
                            {{ document.client_address }}
                        </p>
                        <p v-if="document.client_email">{{ document.client_email }}</p>
                        <p v-if="document.client_phone">{{ document.client_phone }}</p>
                    </div>
                </div>

                <div class="shrink-0 space-y-1.5 text-right text-xs">
                    <div class="flex justify-end gap-6">
                        <span :class="mutedText">{{ document.type === 'receipt' ? 'Receipt date' : 'Issue date' }}</span>
                        <span class="w-24 font-medium">{{ formatDate(document.issue_date) }}</span>
                    </div>
                    <div class="flex justify-end gap-6">
                        <span :class="mutedText">{{ dueLabel(document.type) }}</span>
                        <span class="w-24 font-medium">{{ formatDate(document.due_date) }}</span>
                    </div>
                    <div class="flex justify-end gap-6">
                        <span :class="mutedText">Currency</span>
                        <span class="w-24 font-medium">{{ document.currency }}</span>
                    </div>
                </div>
            </div>

            <table class="mt-8 w-full border-collapse text-[13px]">
                <thead>
                    <tr :class="tableHeadClass">
                        <th class="w-8 px-2 py-2.5 text-left text-[10px] font-semibold tracking-[0.12em] uppercase">#</th>
                        <th class="px-2 py-2.5 text-left text-[10px] font-semibold tracking-[0.12em] uppercase">Description</th>
                        <th class="px-2 py-2.5 text-right text-[10px] font-semibold tracking-[0.12em] uppercase">Qty</th>
                        <th class="px-2 py-2.5 text-right text-[10px] font-semibold tracking-[0.12em] uppercase">Rate</th>
                        <th v-if="hasTax" class="px-2 py-2.5 text-right text-[10px] font-semibold tracking-[0.12em] uppercase">Tax</th>
                        <th class="px-2 py-2.5 text-right text-[10px] font-semibold tracking-[0.12em] uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(item, index) in totals.lines"
                        :key="index"
                        class="border-b border-neutral-100"
                    >
                        <td class="px-2 py-3 text-neutral-400">{{ index + 1 }}</td>
                        <td class="px-2 py-3 font-medium">
                            {{ item.description || 'Item description' }}
                        </td>
                        <td class="px-2 py-3 text-right tabular-nums">
                            {{ formatNumber(item.quantity, 3) }}
                        </td>
                        <td class="px-2 py-3 text-right tabular-nums">
                            {{ formatMoney(item.unit_price, document.currency) }}
                        </td>
                        <td v-if="hasTax" class="px-2 py-3 text-right tabular-nums">
                            {{ formatNumber(item.tax_rate, 2) }}%
                        </td>
                        <td class="px-2 py-3 text-right font-medium tabular-nums">
                            {{ formatMoney(item.line_total, document.currency) }}
                        </td>
                    </tr>
                    <tr v-if="totals.lines.length === 0">
                        <td :colspan="hasTax ? 6 : 5" class="px-2 py-6 text-center text-xs text-neutral-400">
                            Add a line item to see it here.
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-5 flex justify-end">
                <div class="w-full max-w-[300px] text-[13px]">
                    <div class="flex items-center justify-between py-1.5">
                        <span :class="mutedText">Subtotal</span>
                        <span class="font-medium tabular-nums">{{ formatMoney(totals.subtotal, document.currency) }}</span>
                    </div>
                    <div v-if="totals.discountAmount > 0" class="flex items-center justify-between py-1.5">
                        <span :class="mutedText">
                            Discount{{ document.discount_type === 'percent' ? ` (${document.discount_value}%)` : '' }}
                        </span>
                        <span class="font-medium tabular-nums">-{{ formatMoney(totals.discountAmount, document.currency) }}</span>
                    </div>
                    <div v-if="totals.taxAmount > 0" class="flex items-center justify-between py-1.5">
                        <span :class="mutedText">Tax</span>
                        <span class="font-medium tabular-nums">{{ formatMoney(totals.taxAmount, document.currency) }}</span>
                    </div>
                    <div
                        class="mt-2 flex items-center justify-between px-3 py-2.5 text-sm font-bold"
                        :class="[grandClass, theme === 'classic' ? 'px-0' : '']"
                    >
                        <span>{{ document.type === 'receipt' ? 'Amount received' : 'Total' }}</span>
                        <span class="tabular-nums">{{ formatMoney(totals.total, document.currency) }}</span>
                    </div>
                    <template v-if="showBalance">
                        <div class="flex items-center justify-between pt-2 text-xs">
                            <span :class="mutedText">Amount paid</span>
                            <span class="font-medium tabular-nums">{{ formatMoney(amountPaid, document.currency) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-dashed border-neutral-300 pt-2 text-xs font-semibold">
                            <span>Balance due</span>
                            <span class="tabular-nums">{{ formatMoney(balance, document.currency) }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <div
                v-if="notes || terms"
                class="mt-9 grid gap-6 border-t border-neutral-100 pt-5 text-xs sm:grid-cols-2"
            >
                <div v-if="notes">
                    <p class="text-[10px] font-semibold tracking-[0.16em] uppercase" :class="mutedText">Notes</p>
                    <p class="mt-1 whitespace-pre-line text-neutral-600">{{ notes }}</p>
                </div>
                <div v-if="terms">
                    <p class="text-[10px] font-semibold tracking-[0.16em] uppercase" :class="mutedText">Terms &amp; payment</p>
                    <p class="mt-1 whitespace-pre-line text-neutral-600">{{ terms }}</p>
                </div>
            </div>

            <div
                v-if="business.bank_details"
                class="mt-6 border-l-[3px] bg-neutral-50 px-4 py-3 text-xs"
                :class="theme === 'bold' ? 'border-orange-500' : theme === 'classic' ? 'border-[#1e3a5f]' : theme === 'minimal' ? 'border-neutral-900' : 'border-indigo-600'"
            >
                <p class="text-[10px] font-semibold tracking-[0.16em] uppercase" :class="mutedText">Payment details</p>
                <p class="mt-1 whitespace-pre-line text-neutral-600">{{ business.bank_details }}</p>
            </div>

            <div class="mt-10 border-t border-neutral-200 pt-4 text-center text-[10px] text-neutral-400">
                {{ business.name || 'Your business' }}
                <template v-if="business.email"> · {{ business.email }}</template>
                <template v-if="business.phone"> · {{ business.phone }}</template>
                <br />
                Generated with Tailor Studio
            </div>
        </div>
    </div>
</template>
