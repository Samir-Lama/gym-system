<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import Button from '@/Components/ui/button/Button.vue'

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/Components/ui/alert-dialog'

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'

import {
    Eye,
    MoreHorizontal,
    Pencil,
    Trash2,
} from 'lucide-vue-next'

interface MembershipPlan {
    id: number
    name: string
    description: string | null
    price: string
    duration_days: number
    status: string
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PlansPagination {
    data: MembershipPlan[]
    current_page: number
    last_page: number
    total: number
    links: PaginationLink[]
}

defineProps<{
    plans: PlansPagination
}>()

const deletePlan = (planId: number) => {
    router.delete(
        route('membership-plans.destroy', planId),
        {
            preserveScroll: true,
        }
    )
}

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
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Membership Plans
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage gym membership plans and pricing.
                    </p>
                </div>

                <Link :href="route('membership-plans.create')"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                    Add Plan
                </Link>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-4 font-medium text-slate-700">
                                    Name
                                </th>

                                <th class="px-6 py-4 font-medium text-slate-700">
                                    Price
                                </th>

                                <th class="px-6 py-4 font-medium text-slate-700">
                                    Duration
                                </th>

                                <th class="px-6 py-4 font-medium text-slate-700">
                                    Status
                                </th>

                                <th class="px-6 py-4 text-right font-medium text-slate-700">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            <tr v-for="plan in plans.data" :key="plan.id"
                                class="transition-colors hover:bg-slate-50/70">
                                <!-- Name -->
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-slate-900">
                                            {{ plan.name }}
                                        </p>

                                        <p v-if="plan.description"
                                            class="mt-1 max-w-md truncate text-xs text-slate-500">
                                            {{ plan.description }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Price -->
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ formatPrice(plan.price) }}
                                </td>

                                <!-- Duration -->
                                <td class="px-6 py-4 text-slate-600">
                                    {{ plan.duration_days }}
                                    day{{ plan.duration_days === 1 ? '' : 's' }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                        :class="statusClasses(plan.status)">
                                        {{ formatStatus(plan.status) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                    <span class="sr-only">
                                                        Open actions
                                                    </span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem as-child>
                                                    <Link :href="route(
                                                        'membership-plans.show',
                                                        plan.id
                                                    )">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem as-child>
                                                    <Link :href="route(
                                                        'membership-plans.edit',
                                                        plan.id
                                                    )">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator />

                                                <AlertDialog>
                                                    <AlertDialogTrigger as-child>
                                                        <DropdownMenuItem class="text-red-600 focus:text-red-600"
                                                            @select.prevent>
                                                            <Trash2 class="mr-2 h-4 w-4" />
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </AlertDialogTrigger>

                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>
                                                                Delete membership plan?
                                                            </AlertDialogTitle>

                                                            <AlertDialogDescription>
                                                                This will permanently delete
                                                                {{ plan.name }}.
                                                                This action cannot be undone.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>

                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>
                                                                Cancel
                                                            </AlertDialogCancel>

                                                            <AlertDialogAction
                                                                class="bg-red-600 text-white hover:bg-red-700"
                                                                @click="deletePlan(plan.id)">
                                                                Delete plan
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty -->
                            <tr v-if="plans.data.length === 0">
                                <td colspan="5" class="px-6 py-14 text-center">
                                    <p class="font-medium text-slate-700">
                                        No membership plans found.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Create your first plan to get started.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="plans.last_page > 1" class="flex items-center justify-between border-t px-6 py-4">
                    <p class="text-sm text-slate-500">
                        Showing {{ plans.data.length }} of
                        {{ plans.total }} plans
                    </p>

                    <div class="flex gap-1">
                        <template v-for="(link, index) in plans.links" :key="index">
                            <Link v-if="link.url" :href="link.url" class="rounded-md px-3 py-1 text-sm" :class="link.active
                                ? 'bg-slate-900 text-white'
                                : 'text-slate-600 hover:bg-slate-100'
                                " v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>