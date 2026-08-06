<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
</script>

<template>
    <AdminLayout>

        <h1 class="text-3xl font-bold mb-8">
            Dashboard
        </h1>

        <div class="grid grid-cols-4 gap-6">

            <div class="bg-white rounded-xl p-6 shadow">
                Members
            </div>

            <div class="bg-white rounded-xl p-6 shadow">
                Revenue
            </div>

            <div class="bg-white rounded-xl p-6 shadow">
                Check-ins
            </div>

            <div class="bg-white rounded-xl p-6 shadow">
                Expiring
            </div>

        </div>

    </AdminLayout>
</template>