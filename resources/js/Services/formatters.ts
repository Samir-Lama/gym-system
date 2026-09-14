export function formatGender(
    gender: string | null | undefined
): string {
    if (!gender) {
        return 'Not provided'
    }

    const labels: Record<string, string> = {
        male: 'Male',
        female: 'Female',
        other: 'Other',
    }

    return labels[gender] ?? gender
}

export function formatDate(
    date: string | null | undefined
): string {
    if (!date) {
        return 'Not provided'
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(date))
}

export function formatStatus(
    status: string | null | undefined
): string {
    if (!status) {
        return 'Not provided'
    }

    const labels: Record<string, string> = {
        active: 'Active',
        inactive: 'Inactive',
        suspended: 'Suspended',
        cancelled: 'Cancelled',
        pending: 'Pending',
    }

    return labels[status] ?? status
}

export function statusClasses(
    status: string | null | undefined
): string {
    const classes: Record<string, string> = {
        active: 'bg-green-100 text-green-700',
        inactive: 'bg-gray-100 text-gray-700',
        suspended: 'bg-yellow-100 text-yellow-700',
        cancelled: 'bg-red-100 text-red-700',
        pending: 'bg-blue-100 text-blue-700',
    }

    return classes[status ?? ''] ?? 'bg-gray-100 text-gray-700'
}

export function capitalize(value: string | null | undefined): string {
    if (!value) {
        return 'Not provided'
    }

    return value.charAt(0).toUpperCase() + value.slice(1)
}

export function membershipStatusClasses(
    status: string | null | undefined
): string {
    const classes: Record<string, string> = {
        active: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        paused: 'bg-amber-50 text-amber-700 border-amber-200',
        expired: 'bg-slate-100 text-slate-600 border-slate-200',
        cancelled: 'bg-red-50 text-red-700 border-red-200',
    }

    return classes[status ?? '']
        ?? 'bg-slate-100 text-slate-600 border-slate-200'
}