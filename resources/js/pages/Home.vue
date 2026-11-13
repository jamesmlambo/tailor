<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    ChevronDown,
    ClipboardList,
    FileText,
    Plus,
    ReceiptText,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    formatDate,
    formatMoney,
    statusTone,
    TYPE_LABELS,
} from '@/lib/documents';
import type { BusinessDetails, DocumentType } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Home', href: '/' }],
    },
});

type RecentDocument = {
    id: number;
    type: DocumentType;
    type_label: string;
    number: string;
    status: string;
    status_label: string;
    client_name: string;
    issue_date: string;
    total: number;
    currency: string;
};

defineProps<{
    business: BusinessDetails;
    needsProfile: boolean;
    recent: RecentDocument[];
}>();

const typeIcons: Record<DocumentType, typeof FileText> = {
    invoice: FileText,
    quotation: ClipboardList,
    receipt: ReceiptText,
};

const createLinks = [
    {
        type: 'invoice' as DocumentType,
        label: 'Invoice',
        description: 'Ask a client for payment.',
        href: '/documents/create?type=invoice',
    },
    {
        type: 'quotation' as DocumentType,
        label: 'Quotation',
        description: 'Send a price estimate.',
        href: '/documents/create?type=quotation',
    },
    {
        type: 'receipt' as DocumentType,
        label: 'Receipt',
        description: 'Confirm a payment received.',
        href: '/documents/create?type=receipt',
    },
];
</script>

<template>
    <Head title="Home" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">Your studio</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                    {{ business.name || 'Welcome' }}
                </h1>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button size="lg">
                        <Plus />
                        New document
                        <ChevronDown />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-64">
                    <DropdownMenuLabel>Create</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        v-for="link in createLinks"
                        :key="link.type"
                        :as-child="true"
                    >
                        <Link
                            :href="link.href"
                            class="flex w-full cursor-pointer items-start gap-3 py-1"
                        >
                            <component
                                :is="typeIcons[link.type]"
                                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            />
                            <span class="grid gap-0.5">
                                <span class="font-medium">{{ link.label }}</span>
                                <span class="text-xs text-muted-foreground">
                                    {{ link.description }}
                                </span>
                            </span>
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <div
            v-if="needsProfile"
            class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4 dark:border-indigo-900/60 dark:bg-indigo-950/30"
        >
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex size-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <Building2 class="size-4" />
                </div>
                <div>
                    <p class="text-sm font-medium text-indigo-950 dark:text-indigo-100">
                        Set up your business details
                    </p>
                    <p class="mt-0.5 text-sm text-indigo-700/80 dark:text-indigo-300/80">
                        Add your name, address, phone and logo once — they appear on every document.
                    </p>
                </div>
            </div>
            <Button as-child class="bg-indigo-600 text-white hover:bg-indigo-700">
                <Link href="/business">
                    Set up now
                    <ArrowRight />
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader class="flex-row flex-wrap items-center justify-between gap-3">
                <div class="space-y-1.5">
                    <CardTitle>Recent documents</CardTitle>
                    <CardDescription>
                        Your latest 10 invoices, quotations and receipts.
                    </CardDescription>
                </div>
                <Button as-child variant="outline" size="sm">
                    <Link href="/documents">
                        View all
                        <ArrowRight />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent class="px-0">
                <div v-if="recent.length === 0" class="px-6 py-14 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted">
                        <FileText class="size-5 text-muted-foreground" />
                    </div>
                    <p class="mt-4 text-sm font-medium">No documents yet</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                        Use the New document button above to create your first invoice, quotation or receipt.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-sm">
                        <thead>
                            <tr class="border-y bg-muted/40 text-left text-xs text-muted-foreground">
                                <th class="px-6 py-2.5 font-medium">Document</th>
                                <th class="px-3 py-2.5 font-medium">Client</th>
                                <th class="px-3 py-2.5 font-medium">Date</th>
                                <th class="px-3 py-2.5 text-right font-medium">Total</th>
                                <th class="px-6 py-2.5 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="document in recent"
                                :key="document.id"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-6 py-3">
                                    <Link
                                        :href="`/documents/${document.id}`"
                                        class="flex items-center gap-2.5"
                                    >
                                        <span class="flex size-8 items-center justify-center rounded-md bg-muted">
                                            <component
                                                :is="typeIcons[document.type]"
                                                class="size-4 text-muted-foreground"
                                            />
                                        </span>
                                        <span>
                                            <span class="block font-medium hover:underline">
                                                {{ document.number }}
                                            </span>
                                            <span class="block text-xs text-muted-foreground">
                                                {{ TYPE_LABELS[document.type] }}
                                            </span>
                                        </span>
                                    </Link>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="block font-medium">{{ document.client_name || '—' }}</span>
                                </td>
                                <td class="px-3 py-3 text-muted-foreground">
                                    {{ formatDate(document.issue_date) }}
                                </td>
                                <td class="px-3 py-3 text-right font-medium tabular-nums">
                                    {{ formatMoney(document.total, document.currency) }}
                                </td>
                                <td class="px-6 py-3">
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                                        :class="statusTone(document.status)"
                                    >
                                        {{ document.status_label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
