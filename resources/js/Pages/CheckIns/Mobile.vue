<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { MapPin } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
    token: string
    location: string | null
    expiresAt: string
}>()

const form = useForm({
    token: props.token,
})
const remainingSeconds = ref(0)
let countdownTimer: ReturnType<typeof setInterval> | undefined

const updateCountdown = () => {
    remainingSeconds.value = Math.max(
        0,
        Math.ceil((new Date(props.expiresAt).getTime() - Date.now()) / 1000),
    )
}

const countdown = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60)
    const seconds = remainingSeconds.value % 60

    return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

const submit = () => {
    form.post(route('mobile-checkin.store'))
}

onMounted(() => {
    updateCountdown()
    countdownTimer = setInterval(updateCountdown, 1000)
})

onBeforeUnmount(() => clearInterval(countdownTimer))
</script>

<template>
    <Head title="Mobile Check-in" />

    <main class="flex min-h-screen items-center justify-center bg-emerald-950 p-5">
        <section class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="bg-emerald-600 px-7 py-8 text-white">
                <p class="text-sm font-medium text-emerald-100">Gym entrance</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight">Ready to train?</h1>
            </div>

            <div class="space-y-6 p-7">
                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-slate-700">
                    <MapPin class="h-5 w-5 text-emerald-600" />
                    <span class="font-medium capitalize">
                        {{ location?.replaceAll('_', ' ') ?? 'Gym entrance' }}
                    </span>
                </div>

                <div>
                    <p class="text-sm text-slate-500">This entrance code expires in</p>
                    <p class="mt-1 font-mono text-4xl font-bold text-slate-900">{{ countdown }}</p>
                </div>

                <div v-if="form.errors.token || form.errors.member || form.errors.member_id" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
                    {{ form.errors.token ?? form.errors.member ?? form.errors.member_id }}
                </div>

                <Button
                    type="button"
                    class="h-12 w-full text-base"
                    :disabled="form.processing || remainingSeconds === 0"
                    @click="submit"
                >
                    {{ form.processing ? 'Checking in...' : 'Confirm Check-in' }}
                </Button>

                <p class="text-center text-xs leading-5 text-slate-400">
                    Your account and active membership will be verified before check-in.
                </p>
            </div>
        </section>
    </main>
</template>
