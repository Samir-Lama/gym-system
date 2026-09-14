<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { formatDate } from '@/Services/formatters'
import { computed, watch, ref, onBeforeUnmount, onMounted } from 'vue'
import Button from '@/Components/ui/button/Button.vue'
import { useEnumOptions } from '@/Composables/useEnumOptions'

const {
    options: paymentMethods,
    loading: paymentMethodsLoading,
    error: paymentMethodsError,
    fetchOptions: fetchPaymentMethods,
} = useEnumOptions()

const {
    options: paymentStatuses,
    loading: paymentStatusesLoading,
    error: paymentStatusesError,
    fetchOptions: fetchPaymentStatuses,
} = useEnumOptions()

onMounted(() => {
    fetchPaymentMethods('/api/enums/payment-methods')
    fetchPaymentStatuses('/api/enums/payment-statuses')
})

interface ActiveMembership {
    id: number
    price: string
    discount_amount: string
    final_price: string
    discount_reason: string | null
    start_date: string
    end_date: string

    plan: {
        id: number
        name: string
        price: string
    }
}

interface Member {
    id: number
    membership_number: string
    first_name: string
    last_name: string
    memberships: ActiveMembership[]
}
interface Payment {
    id: number
    member_id: number
    amount: string
    payment_method: string
    payment_status: string
    paid_at: string | null
    reference: string | null
    created_at: string
    member?: Member
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PaymentPagination {
    data: Payment[]
    current_page: number
    last_page: number
    total: number
    links: PaginationLink[]
}

interface BillingFilters {
    search?: string
    status?: string
    method?: string
    from_date?: string
    to_date?: string
}

const props = defineProps<{
    payments: PaymentPagination
    filters: BillingFilters
}>()
const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const method = ref(props.filters.method ?? '')
const fromDate = ref(props.filters.from_date ?? '')
const toDate = ref(props.filters.to_date ?? '')
const filterErrors = ref<Record<string, string>>({})
let filterTimeout: ReturnType<typeof setTimeout> | undefined

const applyFilters = () => {
    clearTimeout(filterTimeout)
    filterErrors.value = {}

    router.get(route('billing.index'), {
        search: search.value.trim() || undefined,
        status: status.value || undefined,
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

const clearFilters = () => {
    search.value = ''
    status.value = ''
    method.value = ''
    fromDate.value = ''
    toDate.value = ''
    applyFilters()
}

watch(search, () => {
    clearTimeout(filterTimeout)
    filterTimeout = setTimeout(applyFilters, 300)
}, { flush: 'sync' })

onBeforeUnmount(() => {
    clearTimeout(filterTimeout)
    clearTimeout(searchTimeout)
})

const form = useForm({
    member_id: '',
    member_membership_id: '',
    amount: '',
    payment_method: 'cash',
    payment_status: 'paid',
    paid_at: new Date().toISOString().substring(0, 10),
    reference: '',
    notes: '',
})

const submitPayment = () => {
    form.post(route('payments.store'), {
        preserveScroll: true,

        onSuccess: () => {
            form.reset(
                'member_id',
                'member_membership_id',
                'amount',
                'reference',
                'notes'
            )
            selectedMember.value = null
            memberSearch.value = ''
            memberResults.value = []
            memberSearchRequest++
        },
    })
}

const formatPrice = (value: string | number) => {
    return `Rs. ${Number(value).toLocaleString('en-NP', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}

const activeMembership = computed(() => {
    return selectedMember.value?.memberships[0] ?? null
})

interface SearchMember {
    id: number
    membership_number: string
    first_name: string
    last_name: string
    email: string
    phone: string | null
    memberships: ActiveMembership[]
}

const memberSearch = ref('')
const memberResults = ref<SearchMember[]>([])
const selectedMember = ref<SearchMember | null>(null)
const searchingMembers = ref(false)

let searchTimeout: ReturnType<typeof setTimeout>
let memberSearchRequest = 0

const memberLabel = (member: SearchMember) =>
    `${member.first_name} ${member.last_name}`

watch(memberSearch, (value) => {
    clearTimeout(searchTimeout)
    const requestId = ++memberSearchRequest
    searchingMembers.value = false

    if (selectedMember.value) {
        if (value === memberLabel(selectedMember.value)) {
            memberResults.value = []
            return
        }

        selectedMember.value = null
        form.member_id = ''
        form.member_membership_id = ''
        form.amount = ''
    }

    const query = value.trim()

    if (query.length < 2) {
        memberResults.value = []
        return
    }

    searchTimeout = setTimeout(async () => {
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
    clearTimeout(searchTimeout)
    memberSearchRequest++
    searchingMembers.value = false
    selectedMember.value = member

    memberSearch.value = memberLabel(member)

    memberResults.value = []

    form.member_id = member.id.toString()

    const membership = member.memberships[0] ?? null

    if (membership) {
        form.member_membership_id = membership.id.toString()
        form.amount = membership.final_price
    } else {
        form.member_membership_id = ''
        form.amount = ''
    }
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">

            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    Billing
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Record and manage member payments.
                </p>
            </div>

            <div v-if="paymentMethodsError || paymentStatusesError" role="alert" class="text-sm text-red-600">
                <p v-if="paymentMethodsError">Payment methods: {{ paymentMethodsError }}</p>
                <p v-if="paymentStatusesError">Payment statuses: {{ paymentStatusesError }}</p>
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[380px_1fr]">

                <!-- Payment form -->
                <div class="rounded-xl border bg-white shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-slate-900">
                            Record Payment
                        </h2>
                    </div>

                    <form class="space-y-4 p-6" @submit.prevent="submitPayment">
                        <div class="space-y-2">
                            <div class="relative space-y-2">
                                <label class="text-sm font-medium">
                                    Member
                                </label>

                                <Input v-model="memberSearch" type="text"
                                    placeholder="Search name, membership no., email or phone..." autocomplete="off" />

                                <div v-if="searchingMembers" class="text-xs text-slate-500">
                                    Searching...
                                </div>

                                <div v-if="memberResults.length"
                                    class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border bg-white shadow-lg">
                                    <button v-for="member in memberResults" :key="member.id" type="button"
                                        class="block w-full border-b px-4 py-3 text-left last:border-0 hover:bg-slate-50"
                                        @click="selectMember(member)">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">
                                                    {{ member.first_name }}
                                                    {{ member.last_name }}
                                                </p>

                                                <p class="mt-1 text-xs text-slate-500">
                                                    {{ member.membership_number }}
                                                    ·
                                                    {{ member.email }}
                                                </p>
                                            </div>

                                            <span v-if="member.memberships.length" class="text-xs text-emerald-600">
                                                Active
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            <p v-if="form.errors.member_id" class="text-sm text-red-500">
                                {{ form.errors.member_id }}
                            </p>
                        </div>

                        <div v-if="activeMembership" class="rounded-lg border bg-slate-50 p-4">
                            <p class="text-sm font-medium text-slate-900">
                                {{ activeMembership.plan.name }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ formatDate(activeMembership.start_date) }}
                                –
                                {{ formatDate(activeMembership.end_date) }}
                            </p>

                            <div class="mt-4 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Original price</span>
                                    <span class="font-medium text-slate-700">
                                        {{ formatPrice(activeMembership.price) }}
                                    </span>
                                </div>

                                <div v-if="Number(activeMembership.discount_amount) > 0" class="flex justify-between">
                                    <span class="text-slate-500">Discount</span>
                                    <span class="font-medium text-emerald-600">
                                        - {{ formatPrice(activeMembership.discount_amount) }}
                                    </span>
                                </div>

                                <div class="flex justify-between border-t pt-2">
                                    <span class="font-medium text-slate-700">Final price</span>
                                    <span class="font-semibold text-slate-900">
                                        {{ formatPrice(activeMembership.final_price) }}
                                    </span>
                                </div>
                            </div>

                            <p v-if="activeMembership.discount_reason" class="mt-3 text-xs text-slate-500">
                                Discount reason: {{ activeMembership.discount_reason }}
                            </p>
                        </div>

                        <div v-if="selectedMember && !activeMembership"
                            class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm text-amber-700">
                                This member does not currently have an active membership.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                Amount
                            </label>

                            <Input v-model="form.amount" type="number" step="0.01" readonly />

                            <p v-if="form.errors.amount" class="text-sm text-red-500">
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                Payment Method
                            </label>

                            <select v-model="form.payment_method" :disabled="paymentMethodsLoading"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option v-for="option in paymentMethods" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                Status
                            </label>

                            <select v-model="form.payment_status" :disabled="paymentStatusesLoading"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option v-for="option in paymentStatuses" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                Payment Date
                            </label>

                            <Input v-model="form.paid_at" type="date" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                Reference
                            </label>

                            <Input v-model="form.reference" placeholder="Receipt / transaction ID" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">
                                Notes
                            </label>

                            <Textarea v-model="form.notes" placeholder="Optional notes..." />
                        </div>

                        <Button type="submit" class="w-full" :disabled="form.processing ||
                            !form.member_id ||
                            !activeMembership
                            ">
                            {{
                                form.processing
                                    ? 'Saving...'
                                    : 'Record Payment'
                            }}
                        </Button>
                    </form>
                </div>

                <!-- Payment history -->
                <div class="rounded-xl border bg-white shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-slate-900">
                            Payment History
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ payments.total }} total payments
                        </p>
                    </div>

                    <div class="border-b p-4">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                            <div class="space-y-1">
                                <label for="history-search" class="text-xs font-medium text-slate-600">Search</label>
                                <Input id="history-search" v-model="search" type="search"
                                    placeholder="Search member, membership no. or reference..." />
                            </div>

                            <div class="space-y-1">
                                <label for="history-status" class="text-xs font-medium text-slate-600">Status</label>
                                <select id="history-status" v-model="status" :disabled="paymentStatusesLoading" @change="applyFilters"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">All statuses</option>
                                    <option v-for="option in paymentStatuses" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label for="history-method" class="text-xs font-medium text-slate-600">Method</label>
                                <select id="history-method" v-model="method" :disabled="paymentMethodsLoading" @change="applyFilters"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">All methods</option>
                                    <option v-for="option in paymentMethods" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label for="history-from-date" class="text-xs font-medium text-slate-600">From date</label>
                                <Input id="history-from-date" v-model="fromDate" type="date" @change="applyFilters" />
                            </div>

                            <div class="space-y-1">
                                <label for="history-to-date" class="text-xs font-medium text-slate-600">To date</label>
                                <Input id="history-to-date" v-model="toDate" type="date" @change="applyFilters" />
                            </div>
                        </div>

                        <div v-if="Object.keys(filterErrors).length" role="alert" class="mt-3 text-sm text-red-600">
                            <p v-for="(error, field) in filterErrors" :key="field">{{ error }}</p>
                        </div>

                        <div class="mt-3 flex justify-end">
                            <Button variant="outline" type="button" @click="clearFilters">Clear Filters</Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        Member
                                    </th>

                                    <th class="px-6 py-3 text-left">
                                        Amount
                                    </th>

                                    <th class="px-6 py-3 text-left">
                                        Method
                                    </th>

                                    <th class="px-6 py-3 text-left">
                                        Status
                                    </th>

                                    <th class="px-6 py-3 text-left">
                                        Date
                                    </th>

                                    <th class="px-6 py-3 text-left">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="payment in payments.data" :key="payment.id" class="border-b last:border-0">
                                    <td class="px-6 py-4">
                                        {{ payment.member?.first_name }}
                                        {{ payment.member?.last_name }}
                                    </td>

                                    <td class="px-6 py-4 font-medium">
                                        {{ formatPrice(payment.amount) }}
                                    </td>

                                    <td class="px-6 py-4 capitalize">
                                        {{
                                            payment.payment_method.replace(
                                                '_',
                                                ' '
                                            )
                                        }}
                                    </td>

                                    <td class="px-6 py-4 capitalize">
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                                            :class="{
                                                'border-emerald-200 bg-emerald-50 text-emerald-700':
                                                    payment.payment_status === 'paid',

                                                'border-amber-200 bg-amber-50 text-amber-700':
                                                    payment.payment_status === 'pending',

                                                'border-red-200 bg-red-50 text-red-700':
                                                    payment.payment_status === 'failed',

                                                'border-slate-200 bg-slate-100 text-slate-700':
                                                    payment.payment_status === 'refunded',
                                            }">
                                            {{ payment.payment_status }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        {{
                                            payment.paid_at ? formatDate(payment.paid_at) : 'Not paid'
                                        }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <Link :href="route('payments.show', payment.id)"
                                            :aria-label="`View payment #${payment.id}`"
                                            class="text-sm font-medium text-slate-700 hover:text-slate-900">
                                            View
                                        </Link>
                                    </td>
                                </tr>

                                <tr v-if="!payments.data.length">
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                        No payments recorded yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="payments.last_page > 1" class="flex justify-end gap-1 border-t p-4">
                        <template v-for="(link, index) in payments.links" :key="index">
                            <Link v-if="link.url" :href="link.url" preserve-scroll
                                class="rounded-md px-3 py-1.5 text-sm" :class="link.active
                                    ? 'bg-slate-900 text-white'
                                    : 'hover:bg-slate-100'
                                    " v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
