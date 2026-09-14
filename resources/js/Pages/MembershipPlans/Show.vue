<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'

interface MembershipPlan {
    id: number
    name: string
    description: string | null
    price: string | number
    duration_days: number
    status: string
}

defineProps<{
    plan: MembershipPlan
}>()

const formatPrice = (price: string | number) => {
    return `Rs. ${Number(price).toLocaleString('en-NP', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}
const formatStatus = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1)
}

const statusClasses = (status: string) => {
    return status === 'active'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-slate-200 bg-slate-100 text-slate-600'
}
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-slate-900">
                            {{ plan.name }}
                        </h1>

                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                            :class="statusClasses(plan.status)">
                            {{ formatStatus(plan.status) }}
                        </span>
                    </div>

                    <p class="mt-1 text-sm text-slate-500">
                        Membership plan details
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('membership-plans.index')">
                            Back
                        </Link>
                    </Button>

                    <Button as-child>
                        <Link :href="route(
                            'membership-plans.edit',
                            plan.id
                        )">
                            Edit Plan
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Main details -->
            <div class="rounded-xl border bg-white shadow-sm">
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Plan Information
                    </h2>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Price
                        </p>

                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ formatPrice(plan.price) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Duration
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-800">
                            {{ plan.duration_days }}
                            day{{ plan.duration_days === 1 ? '' : 's' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Status
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-800">
                            {{ formatStatus(plan.status) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-slate-900">
                    Description
                </h2>

                <p class="text-sm leading-6 text-slate-600">
                    {{ plan.description || 'No description provided.' }}
                </p>
            </div>
        </div>
    </AdminLayout>
</template>