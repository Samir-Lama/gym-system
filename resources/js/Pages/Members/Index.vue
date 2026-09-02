<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import Button from "@/Components/ui/button/Button.vue";
import { Input } from "@/Components/ui/input";
import {
    formatDate,
    formatStatus,
    statusClasses,
} from "@/Services/formatters";
import { onMounted, ref, watch } from "vue";
import { useEnumOptions } from "@/Composables/useEnumOptions";

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string | null;
    created_at: string;
    status: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface MembersPagination {
    data: Member[];
    current_page: number;
    last_page: number;
    total: number;
    links: PaginationLink[];
}

interface Filters {
    search: string;
    status: string | null;
}

const props = defineProps<{
    members: MembersPagination;
    filters: Filters;
}>();

const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status ?? "");

const {
    options: statuses,
    loading: statusesLoading,
    fetchOptions: fetchStatuses,
} = useEnumOptions();

onMounted(() => {
    fetchStatuses("/api/enums/member-statuses");
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch(status, () => {
    applyFilters();
});

const applyFilters = () => {
    router.get(
        route("members.index"),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const clearFilters = () => {
    search.value = "";
    status.value = "";
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Members
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage your gym members.
                    </p>
                </div>

                <Link :href="route('members.create')"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Add Member
                </Link>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-[1fr_220px_auto]">

                    <!-- Search -->
                    <div>
                        <Input v-model="search" type="search" placeholder="Search members..." />
                    </div>

                    <!-- Status -->
                    <div>
                        <select v-model="status" :disabled="statusesLoading"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">
                                All statuses
                            </option>

                            <option v-for="option in statuses" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Clear -->
                    <Button type="button" variant="outline" @click="clearFilters" :disabled="!search && !status">
                        Clear
                    </Button>

                </div>
            </div>

            <!-- Members Table -->
            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">

                        <thead class="border-b bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-slate-700">
                                    Name
                                </th>

                                <th class="px-6 py-4 font-semibold text-slate-700">
                                    Email
                                </th>

                                <th class="px-6 py-4 font-semibold text-slate-700">
                                    Phone
                                </th>

                                <th class="px-6 py-4 font-semibold text-slate-700">
                                    Status
                                </th>

                                <th class="px-6 py-4 font-semibold text-slate-700">
                                    Joined
                                </th>

                                <th class="px-6 py-4 text-right font-semibold text-slate-700">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">

                            <!-- Members -->
                            <tr v-for="member in members.data" :key="member.id" class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ member.first_name }}
                                    {{ member.last_name }}
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    {{ member.email }}
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    {{ member.phone || "-" }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                        :class="statusClasses(member.status)">
                                        {{ formatStatus(member.status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-slate-600">
                                    <!-- {{ new Date(member.created_at).toLocaleDateString() }} -->
                                    {{ formatDate(member.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('members.show', member.id)"
                                        class="text-sm font-medium text-slate-700 hover:text-slate-900">
                                        View
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="members.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    No members found.
                                </td>
                            </tr>

                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="members.last_page > 1" class="flex items-center justify-between border-t px-6 py-4">
                    <p class="text-sm text-slate-500">
                        Showing {{ members.data.length }} of {{ members.total }} members
                    </p>

                    <div class="flex gap-1">
                        <template v-for="(link, index) in members.links" :key="index">
                            <Link v-if="link.url" :href="link.url" class="rounded-md px-3 py-1 text-sm" :class="[
                                link.active
                                    ? 'bg-slate-900 text-white'
                                    : 'text-slate-600 hover:bg-slate-100'
                            ]" v-html="link.label" />
                        </template>
                    </div>
                </div>

            </div>

        </div>
    </AdminLayout>

</template>
