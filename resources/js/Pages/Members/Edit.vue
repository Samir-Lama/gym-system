<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

import { Button } from '@/Components/ui/button'
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'

interface Member {
    id: number
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

    notes: string | null
}

const props = defineProps<{
    member: Member
}>()

const form = useForm({
    first_name: props.member.first_name ?? '',
    last_name: props.member.last_name ?? '',
    email: props.member.email ?? '',
    phone: props.member.phone ?? '',
    date_of_birth: props.member.date_of_birth
        ? props.member.date_of_birth.substring(0, 10)
        : '',
    gender: props.member.gender ?? '',

    street: props.member.street ?? '',
    city: props.member.city ?? '',
    state: props.member.state ?? '',
    country: props.member.country ?? '',
    postal_code: props.member.postal_code ?? '',

    emergency_contact_name: props.member.emergency_contact_name ?? '',
    emergency_contact_phone: props.member.emergency_contact_phone ?? '',
    emergency_relationship: props.member.emergency_relationship ?? '',

    notes: props.member.notes ?? '',
})

const submit = () => {
    form.put(route('members.update', props.member.id))
}
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div>
                <div class="mb-2">
                    <Link :href="route('members.show', member.id)"
                        class="text-sm text-muted-foreground hover:underline">
                        ← Back to Member
                    </Link>
                </div>

                <h1 class="text-2xl font-bold">
                    Edit Member
                </h1>

                <p class="text-sm text-muted-foreground">
                    Update {{ member.first_name }} {{ member.last_name }}'s information.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Personal Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Personal Information
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="grid gap-6 md:grid-cols-2">

                        <div class="space-y-2">
                            <Label for="first_name">
                                First Name
                            </Label>

                            <Input id="first_name" v-model="form.first_name" placeholder="John" />

                            <p v-if="form.errors.first_name" class="text-sm text-red-500">
                                {{ form.errors.first_name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="last_name">
                                Last Name
                            </Label>

                            <Input id="last_name" v-model="form.last_name" placeholder="Doe" />

                            <p v-if="form.errors.last_name" class="text-sm text-red-500">
                                {{ form.errors.last_name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">
                                Email
                            </Label>

                            <Input id="email" type="email" v-model="form.email" placeholder="john@example.com" />

                            <p v-if="form.errors.email" class="text-sm text-red-500">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">
                                Phone
                            </Label>

                            <Input id="phone" v-model="form.phone" placeholder="+977 98XXXXXXXX" />

                            <p v-if="form.errors.phone" class="text-sm text-red-500">
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="date_of_birth">
                                Date of Birth
                            </Label>

                            <Input id="date_of_birth" type="date" v-model="form.date_of_birth" />

                            <p v-if="form.errors.date_of_birth" class="text-sm text-red-500">
                                {{ form.errors.date_of_birth }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="gender">
                                Gender
                            </Label>

                            <select id="gender" v-model="form.gender"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">
                                    Select gender
                                </option>

                                <option value="male">
                                    Male
                                </option>

                                <option value="female">
                                    Female
                                </option>

                                <option value="other">
                                    Other
                                </option>
                            </select>

                            <p v-if="form.errors.gender" class="text-sm text-red-500">
                                {{ form.errors.gender }}
                            </p>
                        </div>

                    </CardContent>
                </Card>

                <!-- Address -->
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Address
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="grid gap-6 md:grid-cols-2">

                        <div class="space-y-2 md:col-span-2">
                            <Label for="street">
                                Street
                            </Label>

                            <Input id="street" v-model="form.street" placeholder="Street address" />
                        </div>

                        <div class="space-y-2">
                            <Label for="city">
                                City
                            </Label>

                            <Input id="city" v-model="form.city" placeholder="Kathmandu" />
                        </div>

                        <div class="space-y-2">
                            <Label for="state">
                                State / Province
                            </Label>

                            <Input id="state" v-model="form.state" placeholder="Bagmati" />
                        </div>

                        <div class="space-y-2">
                            <Label for="country">
                                Country
                            </Label>

                            <Input id="country" v-model="form.country" placeholder="Nepal" />
                        </div>

                        <div class="space-y-2">
                            <Label for="postal_code">
                                Postal Code
                            </Label>

                            <Input id="postal_code" v-model="form.postal_code" placeholder="44600" />
                        </div>

                    </CardContent>
                </Card>

                <!-- Emergency Contact -->
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Emergency Contact
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="grid gap-6 md:grid-cols-2">

                        <div class="space-y-2">
                            <Label for="emergency_contact_name">
                                Name
                            </Label>

                            <Input id="emergency_contact_name" v-model="form.emergency_contact_name"
                                placeholder="Jane Doe" />
                        </div>

                        <div class="space-y-2">
                            <Label for="emergency_contact_phone">
                                Phone
                            </Label>

                            <Input id="emergency_contact_phone" v-model="form.emergency_contact_phone"
                                placeholder="+977 98XXXXXXXX" />
                        </div>

                        <div class="space-y-2">
                            <Label for="emergency_relationship">
                                Relationship
                            </Label>

                            <Input id="emergency_relationship" v-model="form.emergency_relationship"
                                placeholder="Parent" />
                        </div>

                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Notes
                        </CardTitle>
                    </CardHeader>

                    <CardContent>
                        <textarea v-model="form.notes" rows="4" placeholder="Additional notes..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />

                        <p v-if="form.errors.notes" class="mt-2 text-sm text-red-500">
                            {{ form.errors.notes }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('members.show', member.id)">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>

                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? 'Updating...'
                                : 'Update Member'
                        }}
                    </Button>
                </div>

            </form>
        </div>
    </AdminLayout>
</template>
