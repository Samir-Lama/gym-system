import {
    Activity,
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    Users,
} from 'lucide-vue-next'
import type { Component } from 'vue'

export interface NavigationItem {
    title: string;
    href: string;
    active: string[];
    icon: Component;
    roles?: string[];
}

export const navigation: NavigationItem[] = [
    {
        title: 'Dashboard',
        href: 'dashboard',
        active: ['dashboard'],
        icon: LayoutDashboard,
    },
    {
        title: 'Members',
        href: 'members.index',
        active: ['members.*', 'member-memberships.*'],
        icon: Users,
        roles: ['Admin', 'Staff'],
    },
    {
        title: 'Check-ins',
        href: 'checkins.index',
        active: ['checkins.*'],
        icon: Activity,
        roles: ['Admin', 'Staff'],
    },
    {
        title: 'Membership Plans',
        href: 'membership-plans.index',
        active: ['membership-plans.*'],
        icon: ClipboardList,
        roles: ['Admin', 'Staff'],
    },
    {
        title: 'Billing',
        href: 'billing.index',
        active: ['billing.*', 'payments.*'],
        icon: CreditCard,
        roles: ['Admin', 'Staff'],
    },
]
