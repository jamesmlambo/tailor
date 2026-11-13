<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Save, Sparkles, Trash2 } from '@lucide/vue';
import DocumentPaper from '@/components/DocumentPaper.vue';
import InputError from '@/components/InputError.vue';
import ThemePicker from '@/components/ThemePicker.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    calculateTotals,
    dueLabel,
    formatMoney,
} from '@/lib/documents';
import type {
    BusinessDetails,
    DiscountType,
    DocumentPaperData,
    DocumentRecord,
    DocumentTheme,
    DocumentType,
    LineItem,
    SelectOption,
    ThemeOption,
} from '@/types';

type FormItem = {
    description: string;
    quantity: string | number;
    unit_price: string | number;
    tax_rate: string | number;
};

type DocumentFormData = {
    type: DocumentType;
    number: string;
    status: string;
    theme: DocumentTheme;
    currency: string;
    issue_date: string;
    due_date: string;
    client_name: string;
    client_email: string;
    client_phone: string;
    client_address: string;
    items: FormItem[];
    discount_type: DiscountType;
    discount_value: number | string;
    amount_paid: number | string;
    notes: string;
    terms: string;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    document: DocumentRecord | null;
    defaults: {
        type: DocumentType;
        number: string;
        status: string;
        theme: DocumentTheme;
        currency: string;
        issue_date: string;
        due_date: string;
        tax_rate: number;
    };
    business: BusinessDetails;
    themes: ThemeOption[];
    currencies: SelectOption[];
    statusGroups: Record<DocumentType, SelectOption[]>;
    typeLabels: Record<DocumentType, string>;
    typePlurals: Record<DocumentType, string>;
    nextNumbers: Record<DocumentType, string>;
}>();

const existing = props.document;

function newItem(taxRate = Number(props.business.default_tax_rate ?? props.defaults.tax_rate ?? 0)): FormItem {
    return {
        description: '',
        quantity: 1,
        unit_price: 0,
        tax_rate: taxRate,
    };
}

const form = useForm<DocumentFormData>({
    type: existing?.type ?? props.defaults.type,
    number: existing?.number ?? props.defaults.number,
    status: existing?.status ?? props.defaults.status,
    theme: existing?.theme ?? props.defaults.theme,
    currency: existing?.currency ?? props.defaults.currency,
    issue_date: existing?.issue_date?.slice(0, 10) ?? props.defaults.issue_date,
    due_date: existing?.due_date?.slice(0, 10) ?? props.defaults.due_date,
    client_name: existing?.client_name ?? '',
    client_email: existing?.client_email ?? '',
    client_phone: existing?.client_phone ?? '',
    client_address: existing?.client_address ?? '',
    items: existing?.items?.length
        ? existing.items.map((item) => ({
              description: item.description,
              quantity: item.quantity,
              unit_price: item.unit_price,
              tax_rate: item.tax_rate,
          }))
        : [newItem()],
    discount_type: existing?.discount_type ?? 'fixed',
    discount_value: existing ? Number(existing.discount_value) : 0,
    amount_paid: existing ? Number(existing.amount_paid) : 0,
    notes: existing?.notes ?? '',
    terms: existing?.terms ?? '',
});

const statusOptions = computed<SelectOption[]>(
    () => props.statusGroups[form.type] ?? [],
);

const totals = computed(() =>
    calculateTotals(
        form.items as LineItem[],
        form.discount_type,
        form.discount_value,
    ),
);

const preview = computed<DocumentPaperData>({
    get: () => ({
        type: form.type,
        number: form.number || 'DRAFT',
        status: form.status,
        theme: form.theme,
        currency: form.currency,
        issue_date: form.issue_date,
        due_date: form.due_date,
        client_name: form.client_name,
        client_email: form.client_email,
        client_phone: form.client_phone,
        client_address: form.client_address,
        discount_type: form.discount_type,
        discount_value: form.discount_value,
        amount_paid: form.amount_paid,
        notes: form.notes,
        terms: form.terms,
        business: props.business,
        items: form.items as LineItem[],
    }),
    set: () => undefined,
});

const pageTitle = computed(() => {
    const type = props.typeLabels[form.type].toLowerCase();

    return props.mode === 'create' ? `New ${type}` : `Edit ${type}`;
});

const backUrl = computed(() =>
    existing ? `/documents/${existing.id}` : '/dashboard',
);

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

watch(
    () => form.type,
    (type) => {
        const options = props.statusGroups[type] ?? [];

        if (!options.some((option) => option.value === form.status)) {
            form.status = options[0]?.value ?? 'draft';
        }

        if (props.mode === 'create') {
            form.number = props.nextNumbers[type] ?? form.number;
        }
    },
);

function addItem(): void {
    const last = form.items[form.items.length - 1];

    form.items.push(
        newItem(
            Number(
                last?.tax_rate ??
                    props.business.default_tax_rate ??
                    props.defaults.tax_rate ??
                    0,
            ),
        ),
    );
}

function removeItem(index: number): void {
    if (form.items.length === 1) {
        form.items[0] = newItem();

        return;
    }

    form.items.splice(index, 1);
}

function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string>)[key];
}

function submit(): void {
    if (existing) {
        form.put(`/documents/${existing.id}`, { preserveScroll: true });
    } else {
        form.post('/documents', { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <Link
                    :href="backUrl"
                    class="mt-0.5 flex size-9 items-center justify-center rounded-md border bg-background text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    <span class="sr-only">Back</span>
                </Link>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
                        {{ pageTitle }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Fill in the details — totals and the preview update as you type.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    class="hidden sm:inline-flex"
                    @click="$inertia.visit(backUrl)"
                >
                    Cancel
                </Button>
                <Button type="button" :disabled="form.processing" @click="submit">
                    <Sparkles v-if="mode === 'create'" />
                    <Save v-else />
                    {{ mode === 'create' ? `Generate ${typeLabels[form.type].toLowerCase()}` : 'Save changes' }}
                </Button>
            </div>
        </div>

        <div
            v-if="hasErrors"
            class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
        >
            Please fix the highlighted fields below and try again.
        </div>

        <form
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_480px]"
            @submit.prevent="submit"
        >
            <div class="min-w-0 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Document details</CardTitle>
                        <CardDescription>
                            Choose the document type, number and dates.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="type">Type</Label>
                            <select id="type" v-model="form.type" class="input-select">
                                <option v-for="(label, value) in typeLabels" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <InputError :message="errorFor('type')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="number">Document number</Label>
                            <Input id="number" v-model="form.number" />
                            <InputError :message="errorFor('number')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select id="status" v-model="form.status" class="input-select">
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="errorFor('status')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="issue_date">
                                {{ form.type === 'receipt' ? 'Receipt date' : 'Issue date' }}
                            </Label>
                            <Input id="issue_date" v-model="form.issue_date" type="date" />
                            <InputError :message="errorFor('issue_date')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="due_date">{{ dueLabel(form.type) }}</Label>
                            <Input id="due_date" v-model="form.due_date" type="date" />
                            <InputError :message="errorFor('due_date')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="currency">Currency</Label>
                            <select id="currency" v-model="form.currency" class="input-select">
                                <option v-for="currency in currencies" :key="currency.value" :value="currency.value">
                                    {{ currency.label }}
                                </option>
                            </select>
                            <InputError :message="errorFor('currency')" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Client details</CardTitle>
                        <CardDescription>
                            Who is this {{ typeLabels[form.type].toLowerCase() }} for?
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="client_name">Client name</Label>
                            <Input id="client_name" v-model="form.client_name" placeholder="Acme Ltd" />
                            <InputError :message="errorFor('client_name')" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="client_email">Client email</Label>
                            <Input id="client_email" v-model="form.client_email" type="email" placeholder="billing@acme.com" />
                            <InputError :message="errorFor('client_email')" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="client_phone">Client phone</Label>
                            <Input id="client_phone" v-model="form.client_phone" placeholder="+254 700 000 000" />
                            <InputError :message="errorFor('client_phone')" />
                        </div>
                        <div class="grid gap-2 sm:row-span-2">
                            <Label for="client_address">Client address</Label>
                            <textarea
                                id="client_address"
                                v-model="form.client_address"
                                rows="4"
                                class="input-textarea"
                                placeholder="Street, city, country"
                            ></textarea>
                            <InputError :message="errorFor('client_address')" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex-row items-start justify-between gap-4">
                        <div class="space-y-1.5">
                            <CardTitle>Line items</CardTitle>
                            <CardDescription>
                                Every line is calculated automatically from quantity and rate.
                            </CardDescription>
                        </div>
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <Plus />
                            Add item
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] border-collapse text-sm">
                                <thead>
                                    <tr class="border-b text-left text-xs text-muted-foreground">
                                        <th class="pb-2 pr-3 font-medium">Description</th>
                                        <th class="w-20 px-2 pb-2 text-right font-medium">Qty</th>
                                        <th class="w-32 px-2 pb-2 text-right font-medium">Unit price</th>
                                        <th class="w-24 px-2 pb-2 text-right font-medium">Tax %</th>
                                        <th class="w-32 px-2 pb-2 text-right font-medium">Amount</th>
                                        <th class="w-10 pb-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b align-top last:border-0">
                                        <td class="py-2 pr-3">
                                            <Input
                                                v-model="item.description"
                                                placeholder="e.g. Bespoke three-piece suit"
                                                :aria-invalid="Boolean(errorFor(`items.${index}.description`))"
                                            />
                                            <InputError :message="errorFor(`items.${index}.description`)" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <Input
                                                v-model="item.quantity"
                                                type="number"
                                                min="0.001"
                                                step="0.01"
                                                class="text-right"
                                            />
                                            <InputError :message="errorFor(`items.${index}.quantity`)" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <Input
                                                v-model="item.unit_price"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="text-right"
                                            />
                                            <InputError :message="errorFor(`items.${index}.unit_price`)" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <Input
                                                v-model="item.tax_rate"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                class="text-right"
                                            />
                                            <InputError :message="errorFor(`items.${index}.tax_rate`)" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2 pt-4 text-right font-medium tabular-nums">
                                            {{ formatMoney(totals.lines[index]?.line_total ?? 0, form.currency) }}
                                        </td>
                                        <td class="py-2 text-right">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                class="text-muted-foreground hover:text-destructive"
                                                @click="removeItem(index)"
                                            >
                                                <Trash2 />
                                                <span class="sr-only">Remove item</span>
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <InputError :message="errorFor('items')" class="mt-2" />
                    </CardContent>
                </Card>

                <div class="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Discount &amp; payment</CardTitle>
                            <CardDescription>
                                Totals are recalculated instantly.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-[130px_1fr] gap-3">
                                <div class="grid gap-2">
                                    <Label for="discount_type">Discount</Label>
                                    <select id="discount_type" v-model="form.discount_type" class="input-select">
                                        <option value="fixed">Fixed</option>
                                        <option value="percent">Percent</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="discount_value">
                                        {{ form.discount_type === 'percent' ? 'Percentage' : 'Amount' }}
                                    </Label>
                                    <Input
                                        id="discount_value"
                                        v-model="form.discount_value"
                                        type="number"
                                        min="0"
                                        :max="form.discount_type === 'percent' ? 100 : undefined"
                                        step="0.01"
                                    />
                                    <InputError :message="errorFor('discount_value')" />
                                </div>
                            </div>

                            <div v-if="form.type !== 'quotation'" class="grid gap-2">
                                <Label for="amount_paid">Amount already paid</Label>
                                <Input
                                    id="amount_paid"
                                    v-model="form.amount_paid"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :placeholder="String(totals.total)"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Leave at 0 to show the full balance due.
                                </p>
                                <InputError :message="errorFor('amount_paid')" />
                            </div>

                            <div class="space-y-1.5 rounded-lg bg-muted/50 p-4 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Subtotal</span>
                                    <span class="font-medium tabular-nums">{{ formatMoney(totals.subtotal, form.currency) }}</span>
                                </div>
                                <div v-if="totals.discountAmount > 0" class="flex justify-between">
                                    <span class="text-muted-foreground">Discount</span>
                                    <span class="font-medium tabular-nums">-{{ formatMoney(totals.discountAmount, form.currency) }}</span>
                                </div>
                                <div v-if="totals.taxAmount > 0" class="flex justify-between">
                                    <span class="text-muted-foreground">Tax</span>
                                    <span class="font-medium tabular-nums">{{ formatMoney(totals.taxAmount, form.currency) }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 text-base font-semibold">
                                    <span>Total</span>
                                    <span class="tabular-nums">{{ formatMoney(totals.total, form.currency) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Notes &amp; terms</CardTitle>
                            <CardDescription>
                                These appear at the bottom of the document.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="notes">Notes</Label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    class="input-textarea"
                                    placeholder="Thank you for your business."
                                ></textarea>
                                <InputError :message="errorFor('notes')" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="terms">Terms &amp; payment</Label>
                                <textarea
                                    id="terms"
                                    v-model="form.terms"
                                    rows="3"
                                    class="input-textarea"
                                    placeholder="Payment due within 14 days."
                                ></textarea>
                                <InputError :message="errorFor('terms')" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Design theme</CardTitle>
                        <CardDescription>
                            Pick the look for this document. It is used on screen and in the PDF.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ThemePicker v-model="form.theme" :themes="themes" />
                        <InputError :message="errorFor('theme')" class="mt-2" />
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3 pb-4">
                    <Button type="button" variant="outline" @click="$inertia.visit(backUrl)">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Sparkles v-if="mode === 'create'" />
                        <Save v-else />
                        {{ mode === 'create' ? `Generate ${typeLabels[form.type].toLowerCase()}` : 'Save changes' }}
                    </Button>
                </div>
            </div>

            <aside class="hidden xl:block">
                <div class="sticky top-20 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium">Live preview</p>
                        <span class="text-xs text-muted-foreground">
                            {{ themes.find((theme) => theme.id === form.theme)?.label }} theme
                        </span>
                    </div>
                    <div class="max-h-[calc(100vh-10rem)] overflow-y-auto rounded-xl border bg-neutral-200/70 p-5 dark:bg-neutral-900/60">
                        <div class="mx-auto w-[794px] origin-top" style="zoom: 0.55">
                            <DocumentPaper :document="preview" class="shadow-2xl" />
                        </div>
                    </div>
                    <p class="text-center text-xs text-muted-foreground">
                        Scroll the preview to see the full document.
                    </p>
                </div>
            </aside>
        </form>
    </div>
</template>
