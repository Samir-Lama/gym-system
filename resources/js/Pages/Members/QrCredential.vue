<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { Printer, ShieldCheck } from 'lucide-vue-next'
import { onMounted } from 'vue'

interface Member {
    id: number
    membership_number: string
    first_name: string
    last_name: string
}

defineProps<{
    member: Member
    qrCode: string
}>()

const printCard = () => window.print()

onMounted(() => {
    // Discard the history decryption key so this bearer credential cannot be restored.
    router.clearHistory()
})
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Membership QR Card
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Print or save this card now. The raw credential is not stored.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('members.show', member.id)">Back to Member</Link>
                    </Button>
                    <Button type="button" @click="printCard">
                        <Printer class="mr-2 h-4 w-4" />
                        Print Card
                    </Button>
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 print:hidden">
                This QR is shown once. Issuing another QR for this member immediately revokes this one.
            </div>

            <section class="mx-auto w-full max-w-sm overflow-hidden rounded-2xl border bg-white shadow-lg print:border-slate-300 print:shadow-none">
                <div class="bg-slate-900 px-6 py-5 text-white">
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-widest text-emerald-300">
                        <ShieldCheck class="h-4 w-4" />
                        Gym Access
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold">
                        {{ member.first_name }} {{ member.last_name }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-300">{{ member.membership_number }}</p>
                </div>

                <div class="p-7 text-center">
                    <div
                        class="mx-auto w-full max-w-[280px] rounded-xl border bg-white p-3 [&_svg]:h-auto [&_svg]:w-full"
                        v-html="qrCode"
                    />
                    <p class="mt-5 text-sm font-medium text-slate-900">Scan at the front desk</p>
                    <p class="mt-1 text-xs text-slate-500">Keep this credential private and report a lost card.</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
