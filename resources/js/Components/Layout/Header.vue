<script setup lang="ts">
import { Bell } from "lucide-vue-next";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const user = page.props.auth.user;
</script>

<template>
    <header
        class="flex h-16 items-center justify-between border-b bg-white px-6"
    >
        <div>
            <h1 class="text-xl font-semibold">
                Dashboard
            </h1>
        </div>

        <div class="flex items-center gap-4">
            <button
                class="rounded-lg p-2 hover:bg-slate-100"
            >
                <Bell class="h-5 w-5" />
            </button>

            <div class="flex items-center gap-2">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200"
                >
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>

                <span class="font-medium">
                    {{ user.name }}
                </span>
            </div>
        </div>
    </header>
</template>