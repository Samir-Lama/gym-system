<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import QrcodeVue from 'qrcode.vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
    checkInUrl: string
    expiresAt: string
    location: string | null
    serverTime: string
}>()

const qrUrl = ref(props.checkInUrl)
const expiresAt = ref(props.expiresAt)
const serverTime = ref(props.serverTime)
const remainingSeconds = ref(0)
const refreshing = ref(false)
let requestStartedAt: number | null = null
const localExpiresAt = ref(0)
let countdownTimer: ReturnType<typeof setInterval> | undefined
let rotationTimer: ReturnType<typeof setTimeout> | undefined
let isUnmounted = false

const syncLocalExpiry = () => {
    const roundTripTime = requestStartedAt
        ? Math.max(0, Date.now() - requestStartedAt)
        : 0
    const serverLifetime = new Date(expiresAt.value).getTime() - new Date(serverTime.value).getTime()

    localExpiresAt.value = Date.now() + Math.max(0, serverLifetime - roundTripTime)
    requestStartedAt = null
}

const updateCountdown = () => {
    remainingSeconds.value = Math.max(
        0,
        Math.ceil((localExpiresAt.value - Date.now()) / 1000),
    )
}

const countdown = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60)
    const seconds = remainingSeconds.value % 60

    return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

const refreshQr = async () => {
    if (refreshing.value || isUnmounted) {
        return
    }

    refreshing.value = true
    remainingSeconds.value = 0
    requestStartedAt = Date.now()

    try {
        const response = await fetch(route('checkins.challenge'), {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        })

        if (!response.ok) {
            throw new Error('Unable to refresh the entrance code.')
        }

        const data = await response.json() as {
            checkInUrl: string
            expiresAt: string
            serverTime: string
        }

        if (isUnmounted) {
            return
        }

        qrUrl.value = data.checkInUrl
        expiresAt.value = data.expiresAt
        serverTime.value = data.serverTime
        syncLocalExpiry()
        scheduleRotation()
    } catch {
        if (!isUnmounted) {
            rotationTimer = setTimeout(refreshQr, 5000)
        }
    } finally {
        refreshing.value = false
    }
}

const scheduleRotation = (retryDelay = 1000) => {
    clearTimeout(rotationTimer)
    updateCountdown()

    const untilRotation = localExpiresAt.value - Date.now()
    const delay = untilRotation > 0 ? untilRotation : retryDelay

    rotationTimer = setTimeout(refreshQr, delay)
}

onMounted(() => {
    syncLocalExpiry()
    scheduleRotation()
    countdownTimer = setInterval(updateCountdown, 1000)
})

onBeforeUnmount(() => {
    isUnmounted = true
    clearInterval(countdownTimer)
    clearTimeout(rotationTimer)
})
</script>

<template>
    <Head title="Entrance Check-in" />

    <main class="flex min-h-screen items-center justify-center bg-slate-950 p-6 text-white">
        <section class="grid w-full max-w-6xl gap-10 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-400">
                    {{ location?.replaceAll('_', ' ') ?? 'Gym entrance' }}
                </p>
                <h1 class="mt-4 max-w-2xl text-5xl font-bold tracking-tight sm:text-7xl">
                    Scan to check in
                </h1>
                <p class="mt-6 max-w-xl text-xl leading-8 text-slate-300">
                    Open your phone camera, scan the code, then confirm your arrival.
                </p>

                <div class="mt-10 inline-flex items-center gap-3 rounded-full border border-slate-700 bg-slate-900 px-5 py-3">
                    <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-400" />
                    <span class="text-sm text-slate-300">Refreshing in</span>
                    <span class="font-mono text-lg font-semibold text-white">{{ countdown }}</span>
                </div>
            </div>

            <div class="mx-auto rounded-[2rem] bg-white p-6 shadow-2xl shadow-emerald-950/40 sm:p-8">
                <div
                    v-if="remainingSeconds > 0"
                    class="h-[min(70vw,32rem)] w-[min(70vw,32rem)]"
                >
                    <QrcodeVue
                        :value="qrUrl"
                        :size="512"
                        :margin="2"
                        level="H"
                        render-as="svg"
                        class="h-full w-full"
                    />
                </div>
                <div
                    v-else
                    class="flex h-[min(70vw,32rem)] w-[min(70vw,32rem)] items-center justify-center text-center text-xl font-semibold text-slate-600"
                >
                    Refreshing entrance code...
                </div>
            </div>
        </section>
    </main>
</template>
