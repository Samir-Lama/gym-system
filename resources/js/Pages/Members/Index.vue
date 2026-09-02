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
import { computed, onMounted, ref, watch } from "vue";
import { useEnumOptions } from "@/Composables/useEnumOptions";
import SortableHeader from '@/Components/SortableHeader.vue'
import {
    Eye,
    MoreHorizontal,
    Pencil,
    Trash2,
} from 'lucide-vue-next';
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
} from '@/Components/ui/alert-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string | null;
    created_at: string;
    joined_at: string;
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
    sort: string;
    direction: 'asc' | 'desc';
}

const props = defineProps<{
    members: MembersPagination;
    filters: Filters;
}>();

const sort = ref(props.filters.sort ?? 'created_at')
const direction = ref<'asc' | 'desc'>(
    props.filters.direction ?? 'desc'
)

const search = ref(props.filters.search ?? "");
const status = ref(props.filters.status ?? "");

const {
    options: statuses,
    loading: statusesLoading,
    fetchOptions: fetchStatuses,
} = useEnumOptions();

const sortBy = (column: string) => {
    if (sort.value === column) {
        direction.value =
            direction.value === 'asc' ? 'desc' : 'asc'
    } else {
        sort.value = column
        direction.value = 'asc'
    }

    applyFilters()
}

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
        route('members.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            sort: sort.value,
            direction: direction.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

const clearFilters = () => {
    search.value = "";
    status.value = "";
};

const deleteMember = (memberId: number) => {
    router.delete(route('members.destroy', memberId), {
        preserveScroll: true,
    });
};

const selectedMembers = ref<number[]>([])
const bulkStatus = ref('')

const allSelected = computed(() => {
    return (
        props.members.data.length > 0 &&
        selectedMembers.value.length === props.members.data.length
    )
})

const toggleAll = () => {
    if (allSelected.value) {
        selectedMembers.value = []
        return
    }

    selectedMembers.value = props.members.data.map(
        member => member.id
    )
}

const applyBulkStatus = () => {
    if (
        selectedMembers.value.length === 0 ||
        !bulkStatus.value
    ) {
        return
    }

    router.post(
        route('members.bulk-status'),
        {
            member_ids: selectedMembers.value,
            status: bulkStatus.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                selectedMembers.value = []
                bulkStatus.value = ''
            },
        }
    )
}

const bulkDelete = () => {
    router.visit(route('members.bulk-delete'), {
        method: 'delete',
        data: {
            member_ids: selectedMembers.value,
        },
        preserveScroll: true,
        onSuccess: () => {
            selectedMembers.value = []
            bulkStatus.value = ''
        },
    })
}
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
                    <div v-if="selectedMembers.length"
                        class="flex flex-col gap-3 border-b bg-slate-50/70 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                                {{ selectedMembers.length }}
                            </div>

                            <p class="text-sm font-medium text-slate-700">
                                member{{ selectedMembers.length === 1 ? '' : 's' }}
                                selected
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <select v-model="bulkStatus"
                                class="h-9 min-w-[170px] rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                                <option value="" disabled>
                                    Change status...
                                </option>

                                <option v-for="option in statuses" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>

                            <Button size="sm" :disabled="!bulkStatus" class="min-w-[72px]" @click="applyBulkStatus">
                                Apply
                            </Button>

                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <Button size="sm" variant="outline"
                                        class="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete selected
                                    </Button>
                                </AlertDialogTrigger>

                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>
                                            Delete selected members?
                                        </AlertDialogTitle>

                                        <AlertDialogDescription>
                                            You are about to permanently delete
                                            {{ selectedMembers.length }}
                                            member{{ selectedMembers.length === 1 ? '' : 's' }}.
                                            This action cannot be undone.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>

                                    <AlertDialogFooter>
                                        <AlertDialogCancel>
                                            Cancel
                                        </AlertDialogCancel>

                                        <AlertDialogAction class="bg-red-600 text-white hover:bg-red-700"
                                            @click="bulkDelete">
                                            Delete members
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </div>

                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="w-12 px-5 py-4">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll"
                                        class="h-4 w-4 rounded border-slate-300" />
                                </th>
                                <th class="px-5 py-4 text-left">
                                    <SortableHeader label="Name" column="first_name" :sort="sort" :direction="direction"
                                        @sort="sortBy" />
                                </th>

                                <th class="px-5 py-4 text-left">
                                    <SortableHeader label="Email" column="email" :sort="sort" :direction="direction"
                                        @sort="sortBy" />
                                </th>

                                <th class="px-5 py-4 text-left text-sm font-medium text-slate-700">
                                    Phone
                                </th>

                                <th class="px-5 py-4 text-left">
                                    <SortableHeader label="Status" column="status" :sort="sort" :direction="direction"
                                        @sort="sortBy" />
                                </th>

                                <th class="px-5 py-4 text-left">
                                    <SortableHeader label="Joined" column="joined_at" :sort="sort"
                                        :direction="direction" @sort="sortBy" />
                                </th>

                                <th class="px-5 py-4 text-right text-sm font-medium text-slate-700">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">

                            <!-- Members -->
                            <tr v-for="member in members.data" :key="member.id" class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <input v-model="selectedMembers" type="checkbox" :value="member.id"
                                        class="h-4 w-4 rounded border-slate-300" />
                                </td>
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
                                    {{ formatDate(member.joined_at) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                    <span class="sr-only">Open actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem as-child>
                                                    <Link :href="route('members.show', member.id)">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem as-child>
                                                    <Link :href="route('members.edit', member.id)">
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
                                                                Delete member?
                                                            </AlertDialogTitle>

                                                            <AlertDialogDescription>
                                                                This will permanently delete
                                                                {{ member.first_name }} {{ member.last_name }}.
                                                                This action cannot be undone.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>

                                                        <AlertDialogFooter>
                                                            <AlertDialogCancel>
                                                                Cancel
                                                            </AlertDialogCancel>

                                                            <AlertDialogAction
                                                                class="bg-red-600 text-white hover:bg-red-700"
                                                                @click="deleteMember(member.id)">
                                                                Delete member
                                                            </AlertDialogAction>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="members.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
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
