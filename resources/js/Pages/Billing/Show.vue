<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Button from '@/Components/ui/button/Button.vue'
import InfoItem from '@/Components/InfoItem.vue'
import { Head, Link } from '@inertiajs/vue3'
import { formatDate } from '@/Services/formatters'

const props = defineProps<{
    payment: {
        id: number
        amount: string
        payment_method: string
        payment_status: string
        paid_at: string | null
        reference: string | null
        notes: string | null

        member: {
            id: number
            membership_number: string
            first_name: string
            last_name: string
            email: string
            phone: string | null
        }

        membership: {
            id: number
            price: string
            discount_amount: string
            final_price: string
            discount_reason: string | null

            plan: {
                id: number
                name: string
            }
        } | null
    }
}>()

const formatPrice = (value: string | number) => {
    return `Rs. ${Number(value).toLocaleString('en-NP', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}
</script>

<template>
    <Head :title="`Payment #${payment.id}`" />
    <AdminLayout>
        <div class="mx-auto max-w-4xl space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">
                        Payment Receipt
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Payment #{{ payment.id }}
                    </p>
                </div>

                <Button variant="outline" as-child>
                    <Link :href="route('billing.index')">
                        Back to Billing
                    </Link>
                </Button>
            </div>

            <div class="rounded-xl border bg-white shadow-sm">
                <div class="border-b px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-slate-900">
                            Payment Information
                        </h2>

                        <span
                            class="rounded-full border px-2.5 py-1 text-xs font-medium capitalize"
                        >
                            {{ payment.payment_status }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-3">
                    <InfoItem
                        label="Amount"
                        :value="formatPrice(payment.amount)"
                    />

                    <InfoItem
                        label="Payment Method"
                        :value="payment.payment_method.replace('_', ' ')"
                    />

                    <InfoItem
                        label="Payment Date"
                        :value="payment.paid_at ? formatDate(payment.paid_at) : 'Not paid'"
                    />

                    <InfoItem
                        label="Reference"
                        :value="payment.reference"
                    />
                </div>
            </div>

            <div class="rounded-xl border bg-white shadow-sm">
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Member
                    </h2>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-3">
                    <InfoItem
                        label="Name"
                        :value="`${payment.member.first_name} ${payment.member.last_name}`"
                    />

                    <InfoItem
                        label="Membership Number"
                        :value="payment.member.membership_number"
                    />

                    <InfoItem
                        label="Email"
                        :value="payment.member.email"
                    />

                    <InfoItem
                        label="Phone"
                        :value="payment.member.phone"
                    />
                </div>
            </div>

            <div
                v-if="payment.membership"
                class="rounded-xl border bg-white shadow-sm"
            >
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Membership
                    </h2>
                </div>

                <div class="space-y-4 p-6">
                    <div class="flex justify-between">
                        <span class="text-slate-500">
                            Plan
                        </span>

                        <span class="font-medium text-slate-900">
                            {{ payment.membership.plan.name }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">
                            Original Price
                        </span>

                        <span>
                            {{ formatPrice(payment.membership.price) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">
                            Discount
                        </span>

                        <span class="text-emerald-600">
                            - {{ formatPrice(payment.membership.discount_amount) }}
                        </span>
                    </div>

                    <div class="flex justify-between border-t pt-4">
                        <span class="font-medium text-slate-700">
                            Final Price
                        </span>

                        <span class="font-semibold text-slate-900">
                            {{ formatPrice(payment.membership.final_price) }}
                        </span>
                    </div>

                    <div
                        v-if="payment.membership.discount_reason"
                        class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600"
                    >
                        Discount reason:
                        {{ payment.membership.discount_reason }}
                    </div>
                </div>
            </div>

            <div
                v-if="payment.notes"
                class="rounded-xl border bg-white p-6 shadow-sm"
            >
                <h2 class="mb-2 font-semibold text-slate-900">
                    Notes
                </h2>

                <p class="text-sm text-slate-600">
                    {{ payment.notes }}
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
