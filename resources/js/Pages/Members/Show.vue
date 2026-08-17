<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/button/Button.vue";
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
    status: string

    notes: string | null
}

defineProps<{
    member: Member;
}>();
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="mb-2">
                        <Link :href="route('members.index')" class="text-sm text-muted-foreground hover:underline">
                            ← Back to Members
                        </Link>
                    </div>

                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold">
                            {{ member.first_name }} {{ member.last_name }}
                        </h1>

                        <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusClasses(member.status)">
                            {{ formatStatus(member.status) }}
                        </span>
                    </div>

                    <p class="mt-1 text-sm text-slate-500">
                        Member #{{ member.membership_number }}
                    </p>

                    <p class="text-sm text-slate-500">
                        Joined {{ formatDate(member.joined_at) }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="route('members.index')">
                        <Button variant="outline">
                            ← Back to Members
                        </Button>
                    </Link>

                    <Link :href="route('members.edit', member.id)">
                        <Button>
                            Edit Member
                        </Button>
                    </Link>
                </div>
            </div>
            <div class="grid gap-6 md:grid-cols-2">

                <!-- Personal Information -->
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Personal Information
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-slate-500">Full Name</p>
                            <p class="font-medium">
                                {{ member.first_name }} {{ member.last_name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Email</p>
                            <p class="font-medium">
                                {{ member.email }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Phone</p>
                            <p class="font-medium">
                                {{ member.phone || "-" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Gender</p>
                            <p class="font-medium">
                                {{ formatGender(member.gender) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Date of Birth</p>
                            <p class="font-medium">
                                <!-- {{ member.date_of_birth || "-" }} -->
                                {{ formatDate(member.date_of_birth) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Address
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-slate-500">Street</p>
                            <p class="font-medium">
                                {{ member.street || "-" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">City</p>
                            <p class="font-medium">
                                {{ member.city || "-" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">State</p>
                            <p class="font-medium">
                                {{ member.state || "-" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Country</p>
                            <p class="font-medium">
                                {{ member.country || "-" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Postal Code</p>
                            <p class="font-medium">
                                {{ member.postal_code || "-" }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Emergency Contact
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-slate-500">Name</p>
                            <p class="font-medium">
                                {{ member.emergency_contact_name || "-" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Phone</p>
                            <p class="font-medium">
                                {{ member.emergency_contact_phone || "-" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Relationship</p>
                            <p class="font-medium">
                                {{ member.emergency_relationship || "-" }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">
                        Notes
                    </h2>

                    <p class="text-slate-600">
                        {{ member.notes || "No notes available." }}
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
