<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/button/Button.vue";
import InfoItem from '@/Components/InfoItem.vue'
import {
    formatGender,
    formatDate,
    formatStatus,
    statusClasses,
} from '@/Services/formatters'

interface Member {
    id: number
    membership_number: string

    first_name: string
    last_name: string
    email: string
    phone: string | null
    date_of_birth: string | null
    gender: string | null

    street: string | null
    city: string | null
    state: string | null
    country: string | null
    postal_code: string | null

    emergency_contact_name: string | null
    emergency_contact_phone: string | null
    emergency_relationship: string | null

    joined_at: string
    notes: string | null
    status: string | null
}

defineProps<{
    member: Member;
}>();
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
        </div>
    </AdminLayout>
</template>