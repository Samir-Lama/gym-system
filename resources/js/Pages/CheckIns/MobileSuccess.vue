<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Check } from 'lucide-vue-next'
</script>

<template>
    <Head title="Check-in Complete" />

    <main class="flex min-h-screen items-center justify-center bg-emerald-950 p-5">
        <section class="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-2xl">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <Check class="h-10 w-10" />
            </div>
            <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">You're checked in</h1>
            <p class="mt-3 text-slate-500">Your visit has been recorded. Have a great workout.</p>

            <Button class="mt-8 w-full" as-child>
                <Link :href="route('dashboard')">Continue</Link>
            </Button>
        </section>
    </main>
</template>
