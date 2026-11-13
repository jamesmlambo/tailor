<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Eye,
    FileText,
    Pencil,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    formatMoney,
    statusTone,
    TYPE_PATHS,
} from '@/lib/documents';
import type {
    BusinessDetails,
    DocumentRecord,
    DocumentSummary,
    DocumentType,
    SelectOption,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Documents', href: '/documents' }],
    },
});

const props = defineProps<{
    type: DocumentType | null;
    title: string;
    documents: DocumentRecord[];
    filters: { search: string; status: string };
    statuses: SelectOption[];
    summary: DocumentSummary;
    business: BusinessDetails;
    currency: string;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const path = computed(() =>
    props.type ? TYPE_PATHS[props.type] : '/documents',
);

const newHref = computed(
    () => `/documents/create?type=${props.type ?? 'invoice'}`,
);

const tabs = [
    { label: 'All', href: '/documents', type: null },
    { label: 'Invoices', href: '/invoices', type: 'invoice' },
    { label: 'Quotations', href: '/quotations', type: 'quotation' },
    { label: 'Receipts', href: '/receipts', type: 'receipt' },
];

function applyFilters(): void {
    const query: Record<string, string> = {};

    if (search.value.trim()) {
        query.search = search.value.trim();
    }

    if (status.value) {
        query.status = status.value;
    }

    router.get(path.value, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters(): void {
    search.value = '';
    status.value = '';
    router.get(path.value, {}, { preserveState: true, replace: true });
}

function destroy(doc: DocumentRecord): void {
    if (!window.confirm(`Delete ${doc.type_label} ${doc.number}? This cannot be undone.`)) {
        return;
    }

    router.delete(`/documents/${doc.id}`, { preserveScroll: true });
}

function statusLabel(doc: DocumentRecord): string {
    return doc.is_overdue ? 'Overdue' : doc.status_label;
}

function statusValue(doc: DocumentRecord): string {
    return doc.is_overdue ? 'overdue' : doc.status;
}
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ summary.count }} {{ summary.count === 1 ? 'document' : 'documents' }}
                    worth {{ formatMoney(summary.total, currency) }}.
                </p>
            </div>
            <Button as-child>
                <Link :href="newHref">
                    <Plus />
                    New {{ type ? type.replace(/s$/, '') : 'invoice' }}
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-1 rounded-lg border bg-muted/40 p-1 text-sm">
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="rounded-md px-3 py-1.5 font-medium transition-colors"
                :class="
                    (props.type ?? null) === tab.type
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
            >
                {{ tab.label }}
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card class="gap-2 py-4">
                <CardContent class="px-5">
                    <p class="text-xs text-muted-foreground">Documents</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ summary.count }}</p>
                </CardContent>
            </Card>
            <Card class="gap-2 py-4">
                <CardContent class="px-5">
                    <p class="text-xs text-muted-foreground">Total value</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ formatMoney(summary.total, currency) }}</p>
                </CardContent>
            </Card>
            <Card class="gap-2 py-4">
                <CardContent class="px-5">
                    <p class="text-xs text-muted-foreground">Paid / accepted</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ formatMoney(summary.paid, currency) }}</p>
                </CardContent>
            </Card>
        </div>

        <Card class="overflow-hidden">
            <CardHeader class="flex-row flex-wrap items-center justify-between gap-3">
                <div class="space-y-1.5">
                    <CardTitle>{{ type ? `All ${title.toLowerCase()}` : 'All documents' }}</CardTitle>
                    <CardDescription>Search by number, client name or email.</CardDescription>
                </div>
                <form class="flex flex-wrap items-center gap-2" @submit.prevent="applyFilters">
                    <div class="relative">
                        <Search class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Search documents…"
                            class="h-9 w-56 pl-8"
                        />
                    </div>
                    <select v-model="status" class="input-select h-9 w-36" @change="applyFilters">
                        <option value="">All statuses</option>
                        <option v-for="option in statuses" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <Button type="submit" variant="outline" size="sm">Filter</Button>
                </form>
            </CardHeader>
            <CardContent class="px-0">
                <div v-if="documents.length" class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="border-y bg-muted/40 text-left text-xs text-muted-foreground">
                                <th class="px-6 py-2.5 font-medium">Document</th>
                                <th class="px-3 py-2.5 font-medium">Client</th>
                                <th class="px-3 py-2.5 font-medium">Issued</th>
                                <th class="px-3 py-2.5 font-medium">Due</th>
                                <th class="px-3 py-2.5 text-right font-medium">Total</th>
                                <th class="px-3 py-2.5 font-medium">Status</th>
                                <th class="px-6 py-2.5 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="doc in documents"
                                :key="doc.id"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-6 py-3">
                                    <Link :href="`/documents/${doc.id}`" class="block">
                                        <span class="font-medium hover:underline">{{ doc.number }}</span>
                                        <span class="mt-0.5 block text-xs text-muted-foreground">{{ doc.type_label }}</span>
                                    </Link>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="block font-medium">{{ doc.client_name }}</span>
                                    <span v-if="doc.client_email" class="block text-xs text-muted-foreground">{{ doc.client_email }}</span>
                                </td>
                                <td class="px-3 py-3 text-muted-foreground">{{ doc.issue_date }}</td>
                                <td class="px-3 py-3 text-muted-foreground">{{ doc.due_date || '—' }}</td>
                                <td class="px-3 py-3 text-right font-medium tabular-nums">
                                    {{ formatMoney(doc.total, doc.currency) }}
                                </td>
                                <td class="px-3 py-3">
                                    <span
                                        class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                                        :class="statusTone(statusValue(doc))"
                                    >
                                        {{ statusLabel(doc) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Link
                                            :href="`/documents/${doc.id}`"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                            title="View"
                                        >
                                            <Eye class="size-4" />
                                        </Link>
                                        <Link
                                            :href="`/documents/${doc.id}/edit`"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                            title="Edit"
                                        >
                                            <Pencil class="size-4" />
                                        </Link>
                                        <button
                                            type="button"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                            title="Delete"
                                            @click="destroy(doc)"
                                        >
                                            <Trash2 class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-muted">
                        <FileText class="size-6 text-muted-foreground" />
                    </div>
                    <p class="mt-3 text-sm font-medium">
                        {{ filters.search || filters.status ? 'No matching documents' : `No ${title.toLowerCase()} yet` }}
                    </p>
                    <p class="mt-1 max-w-xs text-sm text-muted-foreground">
                        {{
                            filters.search || filters.status
                                ? 'Try a different search or clear the filters.'
                                : 'Create your first one and it will appear here.'
                        }}
                    </p>
                    <div class="mt-4 flex gap-2">
                        <Button
                            v-if="filters.search || filters.status"
                            variant="outline"
                            size="sm"
                            @click="clearFilters"
                        >
                            Clear filters
                        </Button>
                        <Button v-else as-child size="sm">
                            <Link :href="newHref">
                                <Plus />
                                Create now
                            </Link>
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
