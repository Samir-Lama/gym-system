<script setup lang="ts">
import Button from '@/Components/ui/button/Button.vue'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Clock3, LogIn, LogOut, Search, UserRound } from 'lucide-vue-next'
import { onBeforeUnmount, ref, watch } from 'vue'

interface MethodOption {
    value: string
    label: string
}

interface ActiveMembership {
    id: number
    end_date: string
    plan: {
        id: number
        name: string
    }
}

interface SearchMember {
    id: number
    membership_number: string
    first_name: string
    last_name: string
    email: string
    phone: string | null
    memberships: ActiveMembership[]
}

interface CheckInRecord {
    id: number
    method: string
    check_in_at: string
    check_out_at: string | null
    device_id: string | null
    notes: string | null
    member: {
        id: number
        membership_number: string
        first_name: string
        last_name: string
    }
    membership: {
        id: number
        plan: {
            id: number
            name: string
        }
    } | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface CheckInPagination {
    data: CheckInRecord[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
    links: PaginationLink[]
}

interface CheckInFilters {
    search: string
    presence: string
    method: string
    from_date: string
    to_date: string
}

const props = defineProps<{
    checkIns: CheckInPagination
    filters: CheckInFilters
    methods: MethodOption[]
}>()

const form = useForm({
    member_id: '',
    method: 'manual',
    device_id: '',
    notes: '',
})

const memberSearch = ref('')
const memberResults = ref<SearchMember[]>([])
const selectedMember = ref<SearchMember | null>(null)
const searchingMembers = ref(false)
let memberSearchTimeout: ReturnType<typeof setTimeout> | undefined
let memberSearchRequest = 0

const memberLabel = (member: SearchMember) =>
    `${member.first_name} ${member.last_name}`

watch(memberSearch, (value) => {
    clearTimeout(memberSearchTimeout)
    const requestId = ++memberSearchRequest
    searchingMembers.value = false

    if (selectedMember.value) {
        if (value === memberLabel(selectedMember.value)) {
            memberResults.value = []
            return
        }

        selectedMember.value = null
        form.member_id = ''
    }

    const query = value.trim()

    if (query.length < 2) {
        memberResults.value = []
        return
    }

    memberSearchTimeout = setTimeout(async () => {
        searchingMembers.value = true

        try {
            const response = await fetch(
                `/api/members/search?search=${encodeURIComponent(query)}`
            )

            if (!response.ok) {
                throw new Error('Member search failed.')
            }

            const results = await response.json()

            if (requestId === memberSearchRequest) {
                memberResults.value = results
            }
        } catch {
            if (requestId === memberSearchRequest) {
                memberResults.value = []
            }
        } finally {
            if (requestId === memberSearchRequest) {
                searchingMembers.value = false
            }
        }
    }, 300)
})

const selectMember = (member: SearchMember) => {
    clearTimeout(memberSearchTimeout)
    memberSearchRequest++
    searchingMembers.value = false
    selectedMember.value = member
    memberSearch.value = memberLabel(member)
    memberResults.value = []
    form.member_id = member.id.toString()
    form.clearErrors('member_id')
}

const clearMember = () => {
    selectedMember.value = null
    memberSearch.value = ''
    memberResults.value = []
    form.member_id = ''
    memberSearchRequest++
}

const submitCheckIn = () => {
    form.post(route('check-ins.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            clearMember()
        },
    })
}

const historySearch = ref(props.filters.search)
const presence = ref(props.filters.presence)
const method = ref(props.filters.method)
const fromDate = ref(props.filters.from_date)
const toDate = ref(props.filters.to_date)
const filterErrors = ref<Record<string, string>>({})
const checkoutError = ref('')
let filterTimeout: ReturnType<typeof setTimeout> | undefined

const applyFilters = () => {
    clearTimeout(filterTimeout)
    filterErrors.value = {}

    router.get(route('check-ins.index'), {
        search: historySearch.value.trim() || undefined,
        presence: presence.value || undefined,
        method: method.value || undefined,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onError: (errors) => { filterErrors.value = errors },
    })
}

watch(historySearch, () => {
    clearTimeout(filterTimeout)
    filterTimeout = setTimeout(applyFilters, 300)
}, { flush: 'sync' })

const clearFilters = () => {
    historySearch.value = ''
    presence.value = ''
    method.value = ''
    fromDate.value = ''
    toDate.value = ''
    applyFilters()
}

const checkOut = (checkIn: CheckInRecord) => {
    checkoutError.value = ''

    router.patch(route('check-ins.checkout', checkIn.id), {}, {
        preserveScroll: true,
        onError: (errors) => {
            checkoutError.value = errors.check_in ?? 'Unable to check out this member.'
        },
    })
}

const formatDateTime = (value: string | null) => {
    if (!value) {
        return 'Still inside'
    }

    return new Intl.DateTimeFormat('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}

const formatDuration = (checkIn: CheckInRecord) => {
    if (!checkIn.check_out_at) {
        return 'In progress'
    }

    const minutes = Math.max(0, Math.round(
        (new Date(checkIn.check_out_at).getTime() - new Date(checkIn.check_in_at).getTime()) / 60000
    ))
    const hours = Math.floor(minutes / 60)
    const remainder = minutes % 60

    return hours ? `${hours}h ${remainder}m` : `${remainder}m`
}

onBeforeUnmount(() => {
    clearTimeout(memberSearchTimeout)
    clearTimeout(filterTimeout)
})
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Check-ins</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Record arrivals and departures while keeping a complete visit history.
                </p>
            </div>

            <div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <section class="h-fit rounded-xl border bg-white shadow-sm">
                    <div class="border-b px-6 py-4">
                        <div class="flex items-center gap-2">
                            <LogIn class="h-5 w-5 text-emerald-600" />
                            <h2 class="font-semibold text-slate-900">Manual Check-in</h2>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Find a member and record their arrival.</p>
                    </div>

                    <form class="space-y-5 p-6" @submit.prevent="submitCheckIn">
                        <div class="relative space-y-2">
                            <label for="member-search" class="text-sm font-medium">Member</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                                <Input
                                    id="member-search"
                                    v-model="memberSearch"
                                    class="pl-9"
                                    autocomplete="off"
                                    placeholder="Name, email or membership number"
                                />
                            </div>
                            <p v-if="searchingMembers" class="text-xs text-slate-500">Searching...</p>
                            <p v-if="form.errors.member_id" class="text-sm text-red-600">
                                {{ form.errors.member_id }}
                            </p>

                            <div
                                v-if="memberResults.length"
                                class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border bg-white p-1 shadow-lg"
                            >
                                <button
                                    v-for="member in memberResults"
                                    :key="member.id"
                                    type="button"
                                    class="w-full rounded-md px-3 py-2 text-left hover:bg-slate-50"
                                    @click="selectMember(member)"
                                >
                                    <span class="block text-sm font-medium text-slate-900">
                                        {{ member.first_name }} {{ member.last_name }}
                                    </span>
                                    <span class="block text-xs text-slate-500">
                                        {{ member.membership_number }} · {{ member.email }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="selectedMember"
                            class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex gap-3">
                                    <div class="rounded-full bg-white p-2 text-emerald-700">
                                        <UserRound class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ memberLabel(selectedMember) }}</p>
                                        <p class="text-xs text-slate-600">{{ selectedMember.membership_number }}</p>
                                        <p v-if="selectedMember.memberships[0]" class="mt-2 text-xs text-emerald-700">
                                            {{ selectedMember.memberships[0].plan.name }} · expires
                                            {{ formatDateTime(selectedMember.memberships[0].end_date) }}
                                        </p>
                                    </div>
                                </div>
                                <button type="button" class="text-xs text-slate-500 hover:text-slate-900" @click="clearMember">
                                    Change
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="check-in-method" class="text-sm font-medium">Method</label>
                            <select
                                id="check-in-method"
                                v-model="form.method"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option v-for="option in methods" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.method" class="text-sm text-red-600">{{ form.errors.method }}</p>
                        </div>

                        <div class="space-y-2">
                            <label for="check-in-device" class="text-sm font-medium">Device ID</label>
                            <Input id="check-in-device" v-model="form.device_id" placeholder="Optional terminal or reader ID" />
                            <p v-if="form.errors.device_id" class="text-sm text-red-600">{{ form.errors.device_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <label for="check-in-notes" class="text-sm font-medium">Notes</label>
                            <Textarea id="check-in-notes" v-model="form.notes" placeholder="Optional visit notes" />
                            <p v-if="form.errors.notes" class="text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>

                        <Button type="submit" class="w-full" :disabled="form.processing || !form.member_id">
                            {{ form.processing ? 'Checking in...' : 'Check In Member' }}
                        </Button>
                    </form>
                </section>

                <section class="min-w-0 rounded-xl border bg-white shadow-sm">
                    <div class="border-b px-6 py-4">
                        <div class="flex items-center gap-2">
                            <Clock3 class="h-5 w-5 text-slate-600" />
                            <h2 class="font-semibold text-slate-900">Visit History</h2>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ checkIns.total }} recorded visits</p>
                    </div>

                    <div class="space-y-3 border-b p-4">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                            <Input v-model="historySearch" type="search" placeholder="Search member..." />
                            <select v-model="presence" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="applyFilters">
                                <option value="">All visits</option>
                                <option value="open">Currently inside</option>
                                <option value="closed">Completed</option>
                            </select>
                            <select v-model="method" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="applyFilters">
                                <option value="">All methods</option>
                                <option v-for="option in methods" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <Input v-model="fromDate" type="date" aria-label="From date" @change="applyFilters" />
                            <Input v-model="toDate" type="date" aria-label="To date" @change="applyFilters" />
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="text-sm text-red-600">
                                <p v-for="(error, field) in filterErrors" :key="field">{{ error }}</p>
                                <p v-if="checkoutError">{{ checkoutError }}</p>
                            </div>
                            <Button type="button" variant="outline" @click="clearFilters">Clear Filters</Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Member</th>
                                    <th class="px-5 py-3 font-medium">Plan</th>
                                    <th class="px-5 py-3 font-medium">Method</th>
                                    <th class="px-5 py-3 font-medium">Check in</th>
                                    <th class="px-5 py-3 font-medium">Check out</th>
                                    <th class="px-5 py-3 font-medium">Duration</th>
                                    <th class="px-5 py-3 font-medium text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="checkIn in checkIns.data" :key="checkIn.id" class="border-b last:border-0">
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-slate-900">
                                            {{ checkIn.member.first_name }} {{ checkIn.member.last_name }}
                                        </p>
                                        <p class="text-xs text-slate-500">{{ checkIn.member.membership_number }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ checkIn.membership?.plan.name ?? 'Historical' }}</td>
                                    <td class="px-5 py-4 capitalize">{{ checkIn.method }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ formatDateTime(checkIn.check_in_at) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ formatDateTime(checkIn.check_out_at) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">{{ formatDuration(checkIn) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <Button
                                            v-if="!checkIn.check_out_at"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="checkOut(checkIn)"
                                        >
                                            <LogOut class="mr-2 h-4 w-4" />
                                            Check Out
                                        </Button>
                                        <span v-else class="text-xs font-medium text-emerald-700">Completed</span>
                                    </td>
                                </tr>
                                <tr v-if="!checkIns.data.length">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">No check-ins found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 border-t px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">
                            Showing {{ checkIns.from ?? 0 }}–{{ checkIns.to ?? 0 }} of {{ checkIns.total }}
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <Link
                                v-for="link in checkIns.links"
                                :key="link.label"
                                :href="link.url ?? ''"
                                preserve-scroll
                                class="rounded-md border px-3 py-1.5 text-sm"
                                :class="[
                                    link.active ? 'border-slate-900 bg-slate-900 text-white' : 'bg-white text-slate-600',
                                    !link.url ? 'pointer-events-none opacity-50' : '',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
