<script setup lang="ts">
defineProps<{
    label: string
    value?: string | null
}>()
</script>

<template>
    <div>
        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
            {{ label }}
        </p>

        <p class="mt-1 text-sm font-medium text-slate-800">
            {{ value || 'Not provided' }}
        </p>
    </div>
</template>