<script setup lang="ts">
import { Check } from '@lucide/vue';
import type { ThemeOption } from '@/types';

defineProps<{
    themes: ThemeOption[];
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <button
            v-for="theme in themes"
            :key="theme.id"
            type="button"
            class="group relative overflow-hidden rounded-xl border bg-card p-3 text-left transition-all hover:border-foreground/30"
            :class="
                modelValue === theme.id
                    ? 'border-foreground/60 ring-2 ring-foreground/10'
                    : 'border-border'
            "
            :aria-pressed="modelValue === theme.id"
            @click="emit('update:modelValue', theme.id)"
        >
            <div class="overflow-hidden rounded-lg border border-black/5 bg-white p-2.5 shadow-sm">
                <div class="h-1.5 w-full rounded-full" :style="{ background: theme.accent }"></div>
                <div class="mt-2.5 flex items-start justify-between gap-2">
                    <div class="space-y-1.5">
                        <div class="h-1.5 w-16 rounded-full bg-neutral-800"></div>
                        <div class="h-1 w-10 rounded-full bg-neutral-300"></div>
                    </div>
                    <div class="h-4 w-9 rounded-sm" :style="{ background: theme.accent, opacity: 0.85 }"></div>
                </div>
                <div class="mt-3 space-y-1">
                    <div class="h-1 w-full rounded-full bg-neutral-200"></div>
                    <div class="h-1 w-4/5 rounded-full bg-neutral-200"></div>
                </div>
                <div class="mt-2.5 flex justify-end gap-1">
                    <div class="h-1.5 w-8 rounded-full" :style="{ background: theme.accent, opacity: 0.25 }"></div>
                    <div class="h-1.5 w-10 rounded-full" :style="{ background: theme.accent }"></div>
                </div>
            </div>

            <div class="mt-3 flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium">{{ theme.label }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ theme.description }}</p>
                </div>
                <span
                    v-if="modelValue === theme.id"
                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-foreground text-background"
                >
                    <Check class="size-3" />
                </span>
            </div>
        </button>
    </div>
</template>
