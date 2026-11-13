<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    ClipboardList,
    FileText,
    House,
    LayoutGrid,
    Plus,
    ReceiptText,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home as homeRoute } from '@/routes';
import type { BusinessDetails, NavItem } from '@/types';

const page = usePage();
const business = computed(
    () => page.props.business as BusinessDetails | undefined,
);

const overviewNavItems: NavItem[] = [
    {
        title: 'Home',
        href: homeRoute(),
        icon: House,
    },
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'All documents',
        href: '/documents',
        icon: FileText,
    },
];

const documentNavItems: NavItem[] = [
    {
        title: 'Invoices',
        href: '/invoices',
        icon: FileText,
    },
    {
        title: 'Quotations',
        href: '/quotations',
        icon: ClipboardList,
    },
    {
        title: 'Receipts',
        href: '/receipts',
        icon: ReceiptText,
    },
];

const settingsNavItems: NavItem[] = [
    {
        title: 'Business details',
        href: '/business',
        icon: Building2,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeRoute()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="overviewNavItems" label="Overview" />
            <NavMain :items="documentNavItems" label="Documents" />
            <NavMain :items="settingsNavItems" label="Settings" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child class="h-auto py-2">
                        <Link href="/business" class="flex items-center gap-2">
                            <span
                                class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted text-xs font-semibold"
                            >
                                <img
                                    v-if="business?.logo_url"
                                    :src="business.logo_url"
                                    alt=""
                                    class="size-full object-contain"
                                />
                                <template v-else>{{ business?.initials || 'B' }}</template>
                            </span>
                            <span class="grid min-w-0 flex-1 text-left text-sm leading-tight">
                                <span class="truncate font-medium">
                                    {{ business?.name || 'Add business details' }}
                                </span>
                                <span class="truncate text-xs text-muted-foreground">
                                    {{ business?.currency || 'USD' }}
                                </span>
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="/documents/create?type=invoice">
                            <Plus />
                            <span>New invoice</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
