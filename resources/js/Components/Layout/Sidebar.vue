<script setup lang="ts">
import { navigation } from '@/config/navigation'
import type { PageProps } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage<PageProps>()

const visibleNavigation = computed(() => navigation.filter((item) =>
    !item.roles || item.roles.some((role) => page.props.auth.roles.includes(role))
))

const isActive = (patterns: string[]) =>
    patterns.some((pattern) => route().current(pattern))
</script>

<template>
    <aside class="w-64 min-h-screen border-r bg-slate-900 text-white">
        <div class="border-b border-slate-700 p-6">
            <h1 class="text-xl font-bold">
                Gym System
            </h1>
        </div>

        <nav class="space-y-1 p-4">
            <Link
                v-for="item in visibleNavigation"
                :key="item.title"
                :href="route(item.href)"
                class="flex items-center gap-3 rounded-lg px-4 py-3 transition-colors"
                :class="[
                    isActive(item.active)
                        ? 'bg-slate-800 text-white'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                ]"
            >
                <component
                    :is="item.icon"
                    class="h-5 w-5"
                />

                <span>{{ item.title }}</span>
            </Link>
        </nav>
    </aside>
</template>
