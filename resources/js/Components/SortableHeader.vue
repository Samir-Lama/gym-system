<script setup lang="ts">
import {
    ArrowUp,
    ArrowDown,
    ArrowUpDown,
} from 'lucide-vue-next'

defineProps<{
    label: string
    column: string
    sort: string
    direction: 'asc' | 'desc'
}>()

defineEmits<{
    sort: [column: string]
}>()
</script>

<template>
    <button type="button"
        class="inline-flex items-center gap-1 rounded px-1 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100"
        @click="$emit('sort', column)">
        {{ label }}

        <ArrowUp v-if="sort === column && direction === 'asc'" class="h-3.5 w-3.5" />

        <ArrowDown v-else-if="sort === column && direction === 'desc'" class="h-3.5 w-3.5" />

        <ArrowUpDown v-else class="h-3.5 w-3.5 text-slate-400" />
    </button>
</template>