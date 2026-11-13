<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Copy,
    Download,
    ExternalLink,
    Pencil,
    Share2,
    Trash2,
} from '@lucide/vue';
import DocumentPaper from '@/components/DocumentPaper.vue';
import ShareDocumentDialog from '@/components/ShareDocumentDialog.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatMoney, statusTone, TYPE_PATHS } from '@/lib/documents';
import type {
    DocumentRecord,
    DocumentType,
    SelectOption,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Documents', href: '/documents' }],
    },
});

const props = defineProps<{
    document: DocumentRecord;
    statuses: SelectOption[];
    typeLabels: Record<DocumentType, string>;
}>();

const shareOpen = ref(false);

const typePath = computed(() => TYPE_PATHS[props.document.type]);
const title = computed(
    () => `${props.document.type_label} ${props.document.number}`,
);

function updateStatus(event: Event): void {
    const target = event.target as HTMLSelectElement;

    router.patch(
        `/documents/${props.document.id}/status`,
        { status: target.value },
        { preserveScroll: true },
    );
}

function duplicate(): void {
    router.post(`/documents/${props.document.id}/duplicate`, {}, { preserveScroll: true });
}

function destroy(): void {
    if (!window.confirm(`Delete ${title.value}? This cannot be undone.`)) {
        return;
    }

    router.delete(`/documents/${props.document.id}`);
}
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <Link
                    :href="typePath"
                    class="mt-0.5 flex size-9 items-center justify-center rounded-md border bg-background text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    <span class="sr-only">Back to {{ document.type_plural.toLowerCase() }}</span>
                </Link>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">{{ title }}</h1>
                        <span
                            class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                            :class="statusTone(document.is_overdue ? 'overdue' : document.status)"
                        >
                            {{ document.is_overdue ? 'Overdue' : document.status_label }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ document.client_name }} · {{ document.formatted_total }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select
                    :value="document.status"
                    class="input-select h-9 w-32"
                    @change="updateStatus"
                >
                    <option v-for="option in statuses" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <Button as-child variant="outline">
                    <Link :href="`/documents/${document.id}/edit`">
                        <Pencil />
                        Edit
                    </Link>
                </Button>
                <Button type="button" variant="outline" @click="duplicate">
                    <Copy />
                    <span class="hidden sm:inline">Duplicate</span>
                </Button>
                <Button as="a" :href="document.pdf_url" variant="outline">
                    <Download />
                    <span class="hidden sm:inline">Download PDF</span>
                </Button>
                <Button type="button" @click="shareOpen = true">
                    <Share2 />
                    Share
                </Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">
            <div class="rounded-xl border bg-neutral-200/70 p-4 md:p-8 dark:bg-neutral-900/60">
                <div class="mx-auto max-w-[860px] shadow-2xl">
                    <DocumentPaper :document="document" />
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-sm font-medium">Share this {{ document.type_label.toLowerCase() }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Send a private link by WhatsApp or email, or attach the PDF.
                    </p>
                    <Button class="mt-3 w-full" @click="shareOpen = true">
                        <Share2 />
                        Open share options
                    </Button>
                    <a
                        :href="document.share_url"
                        target="_blank"
                        class="mt-2 flex items-center justify-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                    >
                        Preview public link
                        <ExternalLink class="size-3" />
                    </a>
                </div>

                <div class="rounded-xl border bg-card p-5 text-sm shadow-sm">
                    <p class="text-sm font-medium">Details</p>
                    <dl class="mt-3 space-y-2 text-xs">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Issued</dt>
                            <dd class="font-medium">{{ formatDate(document.issue_date) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">{{ document.due_label }}</dt>
                            <dd class="font-medium">{{ formatDate(document.due_date) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Currency</dt>
                            <dd class="font-medium">{{ document.currency }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Theme</dt>
                            <dd class="font-medium capitalize">{{ document.theme }}</dd>
                        </div>
                        <div v-if="Number(document.amount_paid) > 0" class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Amount paid</dt>
                            <dd class="font-medium">{{ formatMoney(document.amount_paid, document.currency) }}</dd>
                        </div>
                        <div v-if="document.balance > 0" class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Balance</dt>
                            <dd class="font-medium">{{ formatMoney(document.balance, document.currency) }}</dd>
                        </div>
                    </dl>
                    <Button
                        type="button"
                        variant="outline"
                        class="mt-4 w-full text-destructive hover:bg-destructive/10 hover:text-destructive"
                        @click="destroy"
                    >
                        <Trash2 />
                        Delete document
                    </Button>
                </div>
            </aside>
        </div>
    </div>

    <ShareDocumentDialog v-model:open="shareOpen" :document="document" />
</template>
