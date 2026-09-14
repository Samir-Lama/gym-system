<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Button from "@/Components/ui/button/Button.vue";
import InfoItem from '@/Components/InfoItem.vue'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { Link, router, useForm } from '@inertiajs/vue3'
import {
    formatGender,
    formatDate,
    formatStatus,
    statusClasses,
    capitalize,
    membershipStatusClasses,
} from '@/Services/formatters'
import { computed } from 'vue'
import { QrCode } from 'lucide-vue-next'

interface MembershipPlan {
    id: number
    name: string
    price: string
    duration_days: number
}

interface MemberMembership {
    id: number
    membership_plan_id: number
    start_date: string
    end_date: string
    price: string
    discount_amount: string
    final_price: string
    discount_reason: string | null
    status: string
    notes: string | null
    plan: MembershipPlan
}

interface Member {
    id: number
    membership_number: string

    first_name: string
    last_name: string

    email: string
    phone: string | null

    gender: string | null
    date_of_birth: string | null
    joined_at: string

    street: string | null
    city: string | null
    state: string | null
    country: string | null
    postal_code: string | null

    emergency_contact_name: string | null
    emergency_contact_phone: string | null
    emergency_relationship: string | null

    status: string
    notes: string | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface MembershipPagination {
    data: MemberMembership[]
    current_page: number
    last_page: number
    total: number
    links: PaginationLink[]
}

const props = defineProps<{
    member: Member
    plans: MembershipPlan[]
    memberships: MembershipPagination
    currentMembership: MemberMembership | null
}>()

const membershipForm = useForm({
    member_id: props.member.id,
    membership_plan_id: '',
    start_date: new Date().toISOString().substring(0, 10),
    notes: '',
    discount_amount: '',
    discount_reason: '',
})

const selectedPlan = computed(() => props.plans.find(plan => plan.id === Number(membershipForm.membership_plan_id)))
const finalPrice = computed(() => Math.max(0, Number(selectedPlan.value?.price ?? 0) - Number(membershipForm.discount_amount || 0)))

const assignMembership = () => {
    membershipForm.post(
        route('member-memberships.store'),
        {
            preserveScroll: true,

            onSuccess: () => {
                membershipForm.reset(
                    'membership_plan_id',
                    'notes',
                    'discount_amount',
                    'discount_reason'
                )
            },

            onError: (errors) => {
            },
        }
    )
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-slate-900">
                            {{ member.first_name }} {{ member.last_name }}
                        </h1>

                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                            :class="statusClasses(member.status)">
                            {{ formatStatus(member.status) }}
                        </span>
                    </div>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ member.membership_number }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('members.index')">
                            Back
                        </Link>
                    </Button>

                    <Button as-child>
                        <Link :href="route('members.edit', member.id)">
                            Edit Member
                        </Link>
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        @click="router.post(route('members.qr.issue', member.id))"
                    >
                        <QrCode class="mr-2 h-4 w-4" />
                        Issue QR Card
                    </Button>
                </div>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                            Current Membership
                        </p>

                        <template v-if="currentMembership">
                            <h3 class="mt-2 text-lg font-semibold text-slate-900">
                                {{ currentMembership.plan.name }}
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ formatDate(currentMembership.start_date) }}
                                –
                                {{ formatDate(currentMembership.end_date) }}
                            </p>

                            <dl class="mt-3 space-y-1 text-sm text-slate-700">
                                    <div>Original Price: Rs. {{ Number(currentMembership.price).toLocaleString() }}</div>
                                    <div>Discount: Rs. {{ Number(currentMembership.discount_amount).toLocaleString() }}</div>
                                    <div class="font-semibold">Final Price: Rs. {{ Number(currentMembership.final_price).toLocaleString() }}</div>
                                </dl>
                                <p v-if="currentMembership.discount_reason" class="mt-1 text-sm text-slate-500">{{ currentMembership.discount_reason }}</p>
                        </template>

                        <template v-else>
                            <p class="mt-2 text-sm text-slate-500">
                                No active membership.
                            </p>
                        </template>
                    </div>

                    <span v-if="currentMembership"
                        class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                        :class="membershipStatusClasses(currentMembership.status)">
                        {{ capitalize(currentMembership.status) }}
                    </span>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="rounded-xl border bg-white shadow-sm">
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Personal Information
                    </h2>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-3">
                    <InfoItem label="Email" :value="member.email" />

                    <InfoItem label="Phone" :value="member.phone" />

                    <InfoItem label="Gender" :value="formatGender(member.gender)" />

                    <InfoItem label="Date of birth" :value="formatDate(member.date_of_birth)" />

                    <InfoItem label="Joined" :value="formatDate(member.joined_at)" />
                </div>
            </div>

            <!-- Address -->
            <div class="rounded-xl border bg-white shadow-sm">
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Address
                    </h2>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-3">
                    <InfoItem label="Street" :value="member.street" />
                    <InfoItem label="City" :value="member.city" />
                    <InfoItem label="State" :value="member.state" />
                    <InfoItem label="Country" :value="member.country" />
                    <InfoItem label="Postal code" :value="member.postal_code" />
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="rounded-xl border bg-white shadow-sm">
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-slate-900">
                        Emergency Contact
                    </h2>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-3">
                    <InfoItem label="Name" :value="member.emergency_contact_name" />

                    <InfoItem label="Phone" :value="member.emergency_contact_phone" />

                    <InfoItem label="Relationship" :value="member.emergency_relationship" />
                </div>
            </div>

            <!-- Notes -->
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold text-slate-900">
                    Notes
                </h2>

                <p class="text-sm leading-6 text-slate-600">
                    {{ member.notes || 'No notes available.' }}
                </p>
            </div>

            <div class="rounded-xl border bg-white shadow-sm">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Memberships
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Manage this member's gym memberships.
                        </p>
                    </div>
                </div>

                <div class="grid items-start gap-6 p-6 lg:grid-cols-[360px_1fr]">
                    <!-- Assign membership -->
                    <form v-if="!currentMembership" class="space-y-4" @submit.prevent="assignMembership">
                        <h3 class="text-sm font-semibold text-slate-900">
                            Assign Membership
                        </h3>

                        <!-- Plan -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">
                                Membership Plan
                            </label>

                            <select v-model="membershipForm.membership_plan_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="" disabled>
                                    Select a plan
                                </option>

                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                    {{ plan.name }}
                                    — Rs. {{ Number(plan.price).toLocaleString() }}
                                </option>
                            </select>

                            <p v-if="membershipForm.errors.membership_plan_id" class="text-sm text-red-500">
                                {{ membershipForm.errors.membership_plan_id }}
                            </p>
                        </div>

                        <!-- Start date -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">
                                Start Date
                            </label>

                            <Input v-model="membershipForm.start_date" type="date" />

                            <p v-if="membershipForm.errors.start_date" class="text-sm text-red-500">
                                {{ membershipForm.errors.start_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label for="discount-amount" class="text-sm font-medium text-slate-700">Discount Amount</label>
                            <Input id="discount-amount" v-model="membershipForm.discount_amount" type="number"
                                min="0" :max="selectedPlan?.price" step="0.01" placeholder="0.00" />
                            <p v-if="membershipForm.errors.discount_amount" class="text-sm text-red-500">
                                {{ membershipForm.errors.discount_amount }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label for="discount-reason" class="text-sm font-medium text-slate-700">Discount Reason</label>
                            <Input id="discount-reason" v-model="membershipForm.discount_reason" maxlength="255"
                                placeholder="Student, promotion, staff discount..." />
                            <p v-if="membershipForm.errors.discount_reason" class="text-sm text-red-500">
                                {{ membershipForm.errors.discount_reason }}
                            </p>
                        </div>

                        <p v-if="selectedPlan" class="text-sm font-semibold text-slate-700">
                            Final Price: Rs. {{ finalPrice.toLocaleString('en-NP', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </p>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">
                                Notes
                            </label>

                            <Textarea v-model="membershipForm.notes" placeholder="Optional notes..." />
                        </div>
                        <div v-if="Object.keys(membershipForm.errors).length"
                            class="rounded-md border border-red-200 bg-red-50 p-3">
                            <p v-for="(error, field) in membershipForm.errors" :key="field"
                                class="text-sm text-red-600">
                                {{ field }}: {{ error }}
                            </p>
                        </div>

                        <Button type="submit" :disabled="membershipForm.processing ||
                            !membershipForm.membership_plan_id
                            ">
                            {{
                                membershipForm.processing
                                    ? 'Assigning...'
                                    : 'Assign Membership'
                            }}
                        </Button>
                    </form>
                    <div v-else class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Current Membership
                                </p>

                                <h3 class="mt-2 text-lg font-semibold text-slate-900">
                                    {{ currentMembership.plan.name }}
                                </h3>

                                <p class="mt-1 text-sm text-slate-600">
                                    {{ formatDate(currentMembership.start_date) }}
                                    –
                                    {{ formatDate(currentMembership.end_date) }}
                                </p>

                                <dl class="mt-3 space-y-1 text-sm text-slate-700">
                                    <div>Original Price: Rs. {{ Number(currentMembership.price).toLocaleString() }}</div>
                                    <div>Discount: Rs. {{ Number(currentMembership.discount_amount).toLocaleString() }}</div>
                                    <div class="font-semibold">Final Price: Rs. {{ Number(currentMembership.final_price).toLocaleString() }}</div>
                                </dl>
                                <p v-if="currentMembership.discount_reason" class="mt-1 text-sm text-slate-500">{{ currentMembership.discount_reason }}</p>
                            </div>

                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                                :class="membershipStatusClasses(currentMembership.status)">
                                {{ capitalize(currentMembership.status) }}
                            </span>
                        </div>

                        <div class="mt-5 border-t border-emerald-200 pt-4">
                            <p class="text-sm text-emerald-700">
                                This member already has an active membership.
                            </p>
                        </div>
                    </div>
                    <!-- Current/history -->
                    <div>
                        <h3 class="mb-4 text-sm font-semibold text-slate-900">
                            Membership History
                        </h3>

                        <div v-if="memberships.data.length" class="space-y-3">
                            <div v-for="membership in memberships.data" :key="membership.id"
                                class="rounded-lg border p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-medium text-slate-900">
                                            {{ membership.plan.name }}
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ formatDate(membership.start_date) }}
                                            –
                                            {{ formatDate(membership.end_date) }}
                                        </p>
                                    </div>

                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                                        :class="membershipStatusClasses(membership.status)">
                                        {{ capitalize(membership.status) }}
                                    </span>
                                </div>

                                <dl class="mt-3 space-y-1 text-sm text-slate-700">
                                    <div>Original Price: Rs. {{ Number(membership.price).toLocaleString() }}</div>
                                    <div>Discount: Rs. {{ Number(membership.discount_amount).toLocaleString() }}</div>
                                    <div class="font-semibold">Final Price: Rs. {{ Number(membership.final_price).toLocaleString() }}</div>
                                </dl>
                                <p v-if="membership.discount_reason" class="mt-1 text-sm text-slate-500">{{ membership.discount_reason }}</p>

                                <!-- Actions -->
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <Button v-if="membership.status === 'active'" size="sm" variant="outline" @click="
                                        router.patch(
                                            route('member-memberships.pause', membership.id)
                                        )
                                        ">
                                        Pause
                                    </Button>

                                    <Button v-if="
                                        membership.status === 'active' ||
                                        membership.status === 'paused'
                                    " size="sm" variant="outline" class="text-red-600" @click="
                                        router.patch(
                                            route('member-memberships.cancel', membership.id)
                                        )
                                        ">
                                        Cancel
                                    </Button>

                                    <Button v-if="
                                        membership.status === 'expired' ||
                                        membership.status === 'cancelled'
                                    " size="sm" @click="
                                        router.post(
                                            route('member-memberships.renew', membership.id)
                                        )
                                        ">
                                        Renew
                                    </Button>

                                    <Button v-if="membership.status === 'paused'" size="sm" variant="outline" @click="
                                        router.patch(
                                            route('member-memberships.resume', membership.id)
                                        )
                                        ">
                                        Resume
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-lg border border-dashed p-8 text-center text-sm text-slate-500">
                            No memberships assigned yet.
                        </div>
                        <div v-if="memberships.last_page > 1"
                            class="mt-4 flex items-center justify-between border-t pt-4">
                            <p class="text-sm text-slate-500">
                                Showing {{ memberships.data.length }}
                                of {{ memberships.total }} memberships
                            </p>

                            <div class="flex gap-1">
                                <template v-for="(link, index) in memberships.links" :key="index">
                                    <Link v-if="link.url" :href="link.url" preserve-scroll
                                        class="rounded-md px-3 py-1 text-sm" :class="link.active
                                                ? 'bg-slate-900 text-white'
                                                : 'text-slate-600 hover:bg-slate-100'
                                            " v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
