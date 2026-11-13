<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Copy, ExternalLink, Mail, MessageCircle, Send } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { DocumentRecord } from '@/types';

const props = defineProps<{
    open: boolean;
    document: DocumentRecord;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const emailForm = useForm({
    email: props.document.client_email || '',
});

function copy(value: string): void {
    if (navigator.clipboard) {
        navigator.clipboard
            .writeText(value)
            .then(() => {
                toast.success('Link copied to clipboard.');
            })
            .catch(() => {
                toast.error('Your browser blocked clipboard access.');
            });
    } else {
        toast.error('Your browser blocked clipboard access.');
    }
}

function sendEmail(): void {
    emailForm.post(`/documents/${props.document.id}/email`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Share {{ document.type_label.toLowerCase() }}</DialogTitle>
                <DialogDescription>
                    Send {{ document.number }} by WhatsApp or email, or copy a private link.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-5">
                <div class="space-y-2">
                    <Label>Private link</Label>
                    <div class="flex gap-2">
                        <Input :model-value="document.share_url" readonly class="font-mono text-xs" />
                        <Button type="button" variant="outline" size="icon" @click="copy(document.share_url)">
                            <Copy />
                        </Button>
                        <Button as="a" :href="document.share_url" target="_blank" variant="outline" size="icon">
                            <ExternalLink />
                        </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Anyone with this link can view and download a PDF of the document.
                    </p>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <Button as="a" :href="document.whatsapp_url" target="_blank" class="bg-emerald-600 text-white hover:bg-emerald-700">
                        <MessageCircle />
                        WhatsApp
                    </Button>
                    <Button as="a" :href="document.mailto_url" class="bg-sky-600 text-white hover:bg-sky-700">
                        <Mail />
                        Email app
                    </Button>
                </div>

                <div class="rounded-lg border bg-muted/40 p-4">
                    <p class="text-sm font-medium">Send by email</p>
                    <p class="mt-0.5 mb-3 text-xs text-muted-foreground">
                        Sends an email with the PDF attached through your configured mailer.
                    </p>
                    <form class="flex gap-2" @submit.prevent="sendEmail">
                        <div class="flex-1">
                            <Input
                                v-model="emailForm.email"
                                type="email"
                                placeholder="client@example.com"
                                required
                            />
                            <InputError :message="emailForm.errors.email" class="mt-1" />
                        </div>
                        <Button type="submit" :disabled="emailForm.processing">
                            <Send />
                            Send
                        </Button>
                    </form>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
