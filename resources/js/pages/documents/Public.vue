<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Download, Printer } from '@lucide/vue';
import DocumentPaper from '@/components/DocumentPaper.vue';
import { Button } from '@/components/ui/button';
import type { DocumentRecord } from '@/types';

const props = defineProps<{
    document: DocumentRecord;
}>();

const title = computed(
    () => `${props.document.type_label} ${props.document.number}`,
);

function print(): void {
    window.print();
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-neutral-200/80 px-4 py-6 print:bg-white print:p-0 dark:bg-neutral-950">
        <div class="mx-auto max-w-[880px] space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-neutral-900 text-sm font-semibold text-white dark:bg-white dark:text-neutral-900">
                        {{ document.business.initials || 'B' }}
                    </span>
                    <div>
                        <p class="text-sm font-semibold">
                            {{ document.business.name || 'Document' }}
                        </p>
                        <p class="text-xs text-muted-foreground">{{ title }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button type="button" variant="outline" @click="print">
                        <Printer />
                        Print
                    </Button>
                    <Button as="a" :href="document.public_pdf_url">
                        <Download />
                        Download PDF
                    </Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl shadow-2xl print:rounded-none print:shadow-none">
                <DocumentPaper :document="document" />
            </div>

            <p class="pb-4 text-center text-xs text-muted-foreground print:hidden">
                Shared securely with a private link. Generated with Tailor Studio.
            </p>
        </div>
    </div>
</template>
