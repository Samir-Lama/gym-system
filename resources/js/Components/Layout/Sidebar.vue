<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { navigation } from "@/config/navigation";
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
                v-for="item in navigation"
                :key="item.title"
                :href="route(item.href)"
                class="flex items-center gap-3 rounded-lg px-4 py-3 transition-colors"
                :class="[
                    route().current(item.active)
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