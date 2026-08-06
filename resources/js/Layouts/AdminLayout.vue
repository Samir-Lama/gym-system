<script setup lang="ts">
import Sidebar from '@/Components/Layout/Sidebar.vue'
import Topbar from '@/Components/Layout/Topbar.vue'
</script>

<template>
    <div class="flex min-h-screen bg-slate-100">
        <Sidebar />

        <div class="flex flex-col flex-1">

            <Topbar />

            <main class="p-8">
                <slot />
            </main>

        </div>

    </div>
</template>