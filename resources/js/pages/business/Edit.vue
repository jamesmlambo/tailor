<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, ImagePlus, Save, Trash2, Upload } from '@lucide/vue';
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
import type {
    BusinessDetails,
    DocumentTheme,
    SelectOption,
    ThemeOption,
} from '@/types';

type BusinessFormData = {
    name: string;
    email: string;
    phone: string;
    website: string;
    address: string;
    city: string;
    country: string;
    tax_number: string;
    logo: File | null;
    remove_logo: boolean;
    currency: string;
    default_tax_rate: number | string;
    default_theme: DocumentTheme;
    invoice_prefix: string;
    quotation_prefix: string;
    receipt_prefix: string;
    payment_terms: string;
    bank_details: string;
    notes: string;
};

const props = defineProps<{
    business: BusinessDetails & {
        currency: string;
        default_theme: DocumentTheme;
        default_tax_rate: number | string;
        invoice_prefix: string;
        quotation_prefix: string;
        receipt_prefix: string;
    };
    themes: ThemeOption[];
    currencies: SelectOption[];
    typeLabels: Record<string, string>;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const localLogo = ref<string | null>(null);

const form = useForm<BusinessFormData>({
    name: props.business.name ?? '',
    email: props.business.email ?? '',
    phone: props.business.phone ?? '',
    website: props.business.website ?? '',
    address: props.business.address ?? '',
    city: props.business.city ?? '',
    country: props.business.country ?? '',
    tax_number: props.business.tax_number ?? '',
    logo: null,
    remove_logo: false,
    currency: props.business.currency ?? 'USD',
    default_tax_rate: Number(props.business.default_tax_rate ?? 0),
    default_theme: props.business.default_theme ?? 'modern',
    invoice_prefix: props.business.invoice_prefix ?? 'INV',
    quotation_prefix: props.business.quotation_prefix ?? 'QUO',
    receipt_prefix: props.business.receipt_prefix ?? 'RCP',
    payment_terms: props.business.payment_terms ?? '',
    bank_details: props.business.bank_details ?? '',
    notes: props.business.notes ?? '',
});

const logoPreview = computed(() => {
    if (localLogo.value) {
        return localLogo.value;
    }

    return form.remove_logo ? null : props.business.logo_url;
});

const hasErrors = computed(() => Object.keys(form.errors).length > 0);

function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string>)[key];
}

function pickLogo(): void {
    fileInput.value?.click();
}

function onLogoChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    form.logo = file;
    form.remove_logo = false;

    if (localLogo.value) {
        URL.revokeObjectURL(localLogo.value);
    }

    localLogo.value = file ? URL.createObjectURL(file) : null;
}

function removeLogo(): void {
    if (localLogo.value) {
        URL.revokeObjectURL(localLogo.value);
        localLogo.value = null;
    }

    form.logo = null;
    form.remove_logo = true;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit(): void {
    form.post('/business', {
        forceFormData: true,
        preserveScroll: true,
    });
}

onBeforeUnmount(() => {
    if (localLogo.value) {
        URL.revokeObjectURL(localLogo.value);
    }
});
</script>

<template>
    <Head title="Business details" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">Your studio</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                    Business details
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    These details appear on every invoice, quotation and receipt you generate.
                </p>
            </div>
            <Button type="button" :disabled="form.processing" @click="submit">
                <Save />
                Save changes
            </Button>
        </div>

        <div
            v-if="hasErrors"
            class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
        >
            Please fix the highlighted fields below and try again.
        </div>

        <form
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
            @submit.prevent="submit"
        >
            <div class="min-w-0 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Identity</CardTitle>
                        <CardDescription>
                            Your business name, logo and contact information.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <Label>Logo</Label>
                            <div class="mt-2 flex flex-wrap items-center gap-4">
                                <div class="flex size-20 items-center justify-center overflow-hidden rounded-xl border bg-muted/40">
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Business logo"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                    <Building2 v-else class="size-7 text-muted-foreground" />
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                        @change="onLogoChange"
                                    />
                                    <Button type="button" variant="outline" size="sm" @click="pickLogo">
                                        <Upload />
                                        {{ logoPreview ? 'Replace logo' : 'Upload logo' }}
                                    </Button>
                                    <Button
                                        v-if="logoPreview"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-muted-foreground hover:text-destructive"
                                        @click="removeLogo"
                                    >
                                        <Trash2 />
                                        Remove
                                    </Button>
                                </div>
                            </div>
                            <p class="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
                                <ImagePlus class="size-3.5" />
                                PNG, JPG or WebP up to 2 MB. A square or wide logo works best.
                            </p>
                            <InputError :message="errorFor('logo')" class="mt-1" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="name">Business name</Label>
                            <Input id="name" v-model="form.name" placeholder="Tailor & Co." />
                            <InputError :message="errorFor('name')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="hello@tailor.co" />
                            <InputError :message="errorFor('email')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" placeholder="+254 700 000 000" />
                            <InputError :message="errorFor('phone')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="website">Website</Label>
                            <Input id="website" v-model="form.website" placeholder="tailor.co" />
                            <InputError :message="errorFor('website')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="tax_number">Tax / registration number</Label>
                            <Input id="tax_number" v-model="form.tax_number" placeholder="P051234567X" />
                            <InputError :message="errorFor('tax_number')" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="address">Address</Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                rows="3"
                                class="input-textarea"
                                placeholder="Street, building, suite"
                            ></textarea>
                            <InputError :message="errorFor('address')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="city">City / town</Label>
                            <Input id="city" v-model="form.city" placeholder="Nairobi" />
                            <InputError :message="errorFor('city')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="country">Country</Label>
                            <Input id="country" v-model="form.country" placeholder="Kenya" />
                            <InputError :message="errorFor('country')" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Document defaults</CardTitle>
                        <CardDescription>
                            Used when you create a new document. You can still change these per document.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="currency">Currency</Label>
                            <select id="currency" v-model="form.currency" class="input-select">
                                <option v-for="currency in currencies" :key="currency.value" :value="currency.value">
                                    {{ currency.label }}
                                </option>
                            </select>
                            <InputError :message="errorFor('currency')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="default_tax_rate">Default tax rate (%)</Label>
                            <Input
                                id="default_tax_rate"
                                v-model="form.default_tax_rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                            />
                            <InputError :message="errorFor('default_tax_rate')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="invoice_prefix">Invoice prefix</Label>
                            <Input id="invoice_prefix" v-model="form.invoice_prefix" placeholder="INV" />
                            <InputError :message="errorFor('invoice_prefix')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="quotation_prefix">Quotation prefix</Label>
                            <Input id="quotation_prefix" v-model="form.quotation_prefix" placeholder="QUO" />
                            <InputError :message="errorFor('quotation_prefix')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="receipt_prefix">Receipt prefix</Label>
                            <Input id="receipt_prefix" v-model="form.receipt_prefix" placeholder="RCP" />
                            <InputError :message="errorFor('receipt_prefix')" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label>Default theme</Label>
                            <ThemePicker v-model="form.default_theme" :themes="themes" />
                            <InputError :message="errorFor('default_theme')" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Notes &amp; payment details</CardTitle>
                        <CardDescription>
                            Defaults for the bottom of your documents. You can override them per document.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="payment_terms">Payment terms</Label>
                            <textarea
                                id="payment_terms"
                                v-model="form.payment_terms"
                                rows="3"
                                class="input-textarea"
                                placeholder="Payment due within 14 days."
                            ></textarea>
                            <InputError :message="errorFor('payment_terms')" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="bank_details">Bank / payment details</Label>
                            <textarea
                                id="bank_details"
                                v-model="form.bank_details"
                                rows="3"
                                class="input-textarea"
                                placeholder="Bank name&#10;Account name&#10;Account number"
                            ></textarea>
                            <InputError :message="errorFor('bank_details')" />
                        </div>

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
                    </CardContent>
                </Card>

                <div class="flex justify-end pb-4">
                    <Button type="submit" :disabled="form.processing">
                        <Save />
                        Save changes
                    </Button>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-sm font-medium">Live tips</p>
                    <ul class="mt-3 space-y-2 text-xs text-muted-foreground">
                        <li>• A logo makes every document look professional.</li>
                        <li>• Prefixes are combined with the year and a sequence, e.g. INV-2026-0001.</li>
                        <li>• Defaults save you time, but each document can still be customised.</li>
                    </ul>
                </div>

                <div class="rounded-xl border bg-muted/40 p-5 text-xs text-muted-foreground">
                    <p class="font-medium text-foreground">Preview</p>
                    <p class="mt-1">
                        Head to a document to see how these details look on paper. Changes apply to new documents and to documents that don't have a saved snapshot yet.
                    </p>
                </div>
            </aside>
        </form>
    </div>
</template>
