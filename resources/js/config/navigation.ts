import {
    LayoutDashboard,
    Users,
    CreditCard,
    QrCode,
    CalendarDays,
    ChartColumn,
    Settings,
} from "lucide-vue-next";
import type { Component } from "vue";

export interface NavigationItem {
    title: string;
    href: string;
    active: string;
    icon: Component;
}

export const navigation: NavigationItem[] = [
    {
        title: "Dashboard",
        href: "dashboard",
        active: "dashboard",
        icon: LayoutDashboard,
    },
    // {
    //     title: "Members",
    //     href: "members.index",
    //     active: "members.*",
    //     icon: Users,
    // },
    // {
    //     title: "Memberships",
    //     href: "memberships.index",
    //     active: "memberships.*",
    //     icon: CreditCard,
    // },
    // {
    //     title: "Check-ins",
    //     href: "checkins.index",
    //     active: "checkins.*",
    //     icon: QrCode,
    // },
    // {
    //     title: "Classes",
    //     href: "classes.index",
    //     active: "classes.*",
    //     icon: CalendarDays,
    // },
    // {
    //     title: "Reports",
    //     href: "reports.index",
    //     active: "reports.*",
    //     icon: ChartColumn,
    // },
    // {
    //     title: "Settings",
    //     href: "settings.index",
    //     active: "settings.*",
    //     icon: Settings,
    // },
];