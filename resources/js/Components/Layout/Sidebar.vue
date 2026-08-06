<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const navigation = [
    { name: 'Dashboard', href: '/dashboard' },
    { name: 'Members', href: '/members' },
    { name: 'Memberships', href: '/memberships' },
    { name: 'Billing', href: '/billing' },
    { name: 'Check-ins', href: '/check-ins' },
    { name: 'Classes', href: '/classes' },
    { name: 'Reports', href: '/reports' },
    { name: 'Settings', href: '/settings' },
]
</script>

<template>
    <aside class="w-64 bg-slate-900 text-white min-h-screen">
        <div class="p-6 border-b border-slate-700">
            <h1 class="text-xl font-bold">
                Gym System
            </h1>
        </div>

        <nav class="p-4 space-y-2">
            <Link
                v-for="item in navigation"
                :key="item.name"
                :href="item.href"
                class="block rounded-lg px-4 py-2 hover:bg-slate-800"
            >
                {{ item.name }}
            </Link>
        </nav>
    </aside>
</template>