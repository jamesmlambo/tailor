<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Building2,
    ClipboardList,
    FileText,
    Plus,
    ReceiptText,
    Sparkles,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney, statusTone, TYPE_PATHS } from '@/lib/documents';
import type { BusinessDetails, DocumentType } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
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

const props = defineProps<{
    business: BusinessDetails & { default_currency: string };
    needsProfile: boolean;
    stats: {
        paid: number;
        paid_count: number;
        outstanding: number;
        outstanding_count: number;
        quotations: number;
        quotations_count: number;
        this_month: number;
    };
    recent: RecentDocument[];
    typeBreakdown: {
        type: DocumentType;
        label: string;
        count: number;
        total: number;
    }[];
    currency: string;
    defaultTaxRate: number;
}>();

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const statCards = computed(() => [
    {
        label: 'Paid invoices',
        value: formatMoney(props.stats.paid, props.currency),
        hint: `${props.stats.paid_count} paid`,
        icon: Wallet,
        href: '/invoices?status=paid',
        tone: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300',
    },
    {
        label: 'Outstanding',
        value: formatMoney(props.stats.outstanding, props.currency),
        hint: `${props.stats.outstanding_count} awaiting payment`,
        icon: TrendingUp,
        href: '/invoices?status=sent',
        tone: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-300',
    },
    {
        label: 'Open quotations',
        value: formatMoney(props.stats.quotations, props.currency),
        hint: `${props.stats.quotations_count} total`,
        icon: ClipboardList,
        href: '/quotations',
        tone: 'bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-300',
    },
    {
        label: 'Paid this month',
        value: formatMoney(props.stats.this_month, props.currency),
        hint: new Date().toLocaleString(undefined, { month: 'long', year: 'numeric' }),
        icon: Sparkles,
        href: '/invoices',
        tone: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300',
    },
]);

const typeIcons: Record<DocumentType, typeof FileText> = {
    invoice: FileText,
    quotation: ClipboardList,
    receipt: ReceiptText,
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">{{ greeting }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                    {{ business.name || 'Your invoice studio' }}
                </h1>
                <p class="mt-1 max-w-xl text-sm text-muted-foreground">
                    Create polished invoices, quotations and receipts — then download or share them in one click.
                </p>
            </div>
            <div class="flex gap-2">
                <Button as-child variant="outline">
                    <Link href="/business">
                        <Building2 />
                        Business details
                    </Link>
                </Button>
                <Button as-child>
                    <Link href="/documents/create?type=invoice">
                        <Plus />
                        New invoice
                    </Link>
                </Button>
            </div>
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
                        Add your business details first
                    </p>
                    <p class="mt-0.5 text-sm text-indigo-700/80 dark:text-indigo-300/80">
                        Your name, address, phone and logo appear on every document.
                    </p>
                </div>
            </div>
            <Button as-child class="bg-indigo-600 text-white hover:bg-indigo-700">
                <Link href="/business">
                    Complete profile
                    <ArrowUpRight />
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="stat in statCards"
                :key="stat.label"
                :href="stat.href"
                class="group rounded-xl border bg-card p-5 shadow-sm transition-all hover:border-foreground/20 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">{{ stat.label }}</p>
                    <div class="flex size-9 items-center justify-center rounded-lg" :class="stat.tone">
                        <component :is="stat.icon" class="size-4" />
                    </div>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight tabular-nums">
                    {{ stat.value }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">{{ stat.hint }}</p>
            </Link>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <Card class="overflow-hidden">
                <CardHeader class="flex-row items-center justify-between gap-3">
                    <div class="space-y-1.5">
                        <CardTitle>Recent documents</CardTitle>
                        <CardDescription>Your latest invoices, quotations and receipts.</CardDescription>
                    </div>
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/documents">
                            View all
                            <ArrowUpRight />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="px-0">
                    <div v-if="recent.length" class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-sm">
                            <thead>
                                <tr class="border-y bg-muted/40 text-left text-xs text-muted-foreground">
                                    <th class="px-6 py-2.5 font-medium">Document</th>
                                    <th class="px-3 py-2.5 font-medium">Client</th>
                                    <th class="px-3 py-2.5 font-medium">Date</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Total</th>
                                    <th class="px-3 py-2.5 font-medium">Status</th>
                                    <th class="px-6 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="doc in recent"
                                    :key="doc.id"
                                    class="border-b last:border-0 hover:bg-muted/30"
                                >
                                    <td class="px-6 py-3">
                                        <Link :href="`/documents/${doc.id}`" class="group block">
                                            <span class="font-medium group-hover:underline">{{ doc.number }}</span>
                                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ doc.type_label }}</span>
                                        </Link>
                                    </td>
                                    <td class="px-3 py-3">{{ doc.client_name }}</td>
                                    <td class="px-3 py-3 text-muted-foreground">{{ doc.issue_date }}</td>
                                    <td class="px-3 py-3 text-right font-medium tabular-nums">
                                        {{ formatMoney(doc.total, doc.currency) }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span
                                            class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                                            :class="statusTone(doc.status)"
                                        >
                                            {{ doc.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <Link
                                            :href="`/documents/${doc.id}`"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                        >
                                            <ArrowUpRight class="size-4" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center px-6 py-14 text-center">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-muted">
                            <FileText class="size-6 text-muted-foreground" />
                        </div>
                        <p class="mt-3 text-sm font-medium">No documents yet</p>
                        <p class="mt-1 max-w-xs text-sm text-muted-foreground">
                            Generate your first invoice or quotation and it will show up here.
                        </p>
                        <Button as-child class="mt-4" size="sm">
                            <Link href="/documents/create?type=invoice">
                                <Plus />
                                New invoice
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Create something</CardTitle>
                        <CardDescription>Start a new document in seconds.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link
                            v-for="(label, type) in { invoice: 'Invoice', quotation: 'Quotation', receipt: 'Receipt' }"
                            :key="type"
                            :href="`/documents/create?type=${type}`"
                            class="flex items-center gap-3 rounded-lg border px-3 py-3 text-sm transition-colors hover:bg-accent"
                        >
                            <span class="flex size-8 items-center justify-center rounded-md bg-muted">
                                <component :is="typeIcons[type as DocumentType]" class="size-4" />
                            </span>
                            <span class="flex-1 font-medium">New {{ label.toLowerCase() }}</span>
                            <Plus class="size-4 text-muted-foreground" />
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>By document type</CardTitle>
                        <CardDescription>Everything you have created so far.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Link
                            v-for="item in typeBreakdown"
                            :key="item.type"
                            :href="TYPE_PATHS[item.type]"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="flex items-center gap-2 text-muted-foreground">
                                <component :is="typeIcons[item.type]" class="size-4" />
                                {{ item.label }}
                            </span>
                            <span class="text-right">
                                <span class="block font-medium tabular-nums">{{ formatMoney(item.total, currency) }}</span>
                                <span class="block text-xs text-muted-foreground">
                                    {{ item.count }} {{ item.count === 1 ? 'document' : 'documents' }}
                                </span>
                            </span>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
