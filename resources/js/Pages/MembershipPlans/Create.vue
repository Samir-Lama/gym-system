<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { onMounted } from 'vue'
import { useEnumOptions } from '@/Composables/useEnumOptions'

const {
    options: statuses,
    loading: statusesLoading,
    fetchOptions: fetchStatuses,
} = useEnumOptions()

onMounted(() => {
    fetchStatuses('/api/enums/membership-plan-statuses')
})

const form = useForm({
    name: '',
    description: '',
    price: '',
    duration_days: '',
    status: 'active',
})

const submit = () => {
    form.post(route('membership-plans.store'))
}
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Create Membership Plan
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Add a new membership plan for your gym.
                    </p>
                </div>

                <Button variant="outline" as-child>
                    <Link :href="route('membership-plans.index')">
                        Back
                    </Link>
                </Button>
            </div>

            <!-- Form -->
            <form class="space-y-6 rounded-xl border bg-white p-6 shadow-sm" @submit.prevent="submit">
                <!-- Name -->
                <div class="space-y-2">
                    <label for="name" class="text-sm font-medium text-slate-700">
                        Plan name
                    </label>

                    <Input id="name" v-model="form.name" type="text" placeholder="Monthly Basic" />

                    <p v-if="form.errors.name" class="text-sm text-red-500">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <label for="description" class="text-sm font-medium text-slate-700">
                        Description
                    </label>

                    <Textarea id="description" v-model="form.description"
                        placeholder="Describe what this plan includes..." rows="4" />

                    <p v-if="form.errors.description" class="text-sm text-red-500">
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Price + Duration -->
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="price" class="text-sm font-medium text-slate-700">
                            Price
                        </label>

                        <Input id="price" v-model="form.price" type="number" min="0" step="0.01" placeholder="29.99" />

                        <p v-if="form.errors.price" class="text-sm text-red-500">
                            {{ form.errors.price }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="duration_days" class="text-sm font-medium text-slate-700">
                            Duration (days)
                        </label>

                        <Input id="duration_days" v-model="form.duration_days" type="number" min="1" placeholder="30" />

                        <p v-if="form.errors.duration_days" class="text-sm text-red-500">
                            {{ form.errors.duration_days }}
                        </p>
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <label for="status" class="text-sm font-medium text-slate-700">
                        Status
                    </label>

                    <select id="status" v-model="form.status" :disabled="statusesLoading"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <option v-for="option in statuses" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <p v-if="form.errors.status" class="text-sm text-red-500">
                        {{ form.errors.status }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 border-t pt-5">
                    <Button type="button" variant="outline" as-child>
                        <Link :href="route('membership-plans.index')">
                            Cancel
                        </Link>
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Plan' }}
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>