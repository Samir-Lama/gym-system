<script setup lang="ts">
import Sidebar from '@/Components/Layout/Sidebar.vue'
import Header from '@/Components/Layout/Header.vue'
import { Toaster } from '@/Components/ui/sonner'
import FlashToaster from '@/Components/Shared/FlashToaster.vue'
</script>

<template>
    <div class="flex min-h-screen bg-slate-100">
        <Sidebar />

        <div class="flex flex-1 flex-col">
            <Header />

            <Toaster position="top-right" />

            <FlashToaster />

            <main class="flex-1 overflow-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>