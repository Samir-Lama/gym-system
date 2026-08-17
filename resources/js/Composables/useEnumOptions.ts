import { ref } from 'vue'
import axios from 'axios'

interface EnumOption {
    value: string
    label: string
}

export function useEnumOptions() {
    const options = ref<EnumOption[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    const fetchOptions = async (url: string) => {
        loading.value = true
        error.value = null

        try {
            const response = await axios.get<EnumOption[]>(url)
            options.value = response.data
        } catch (err) {
            error.value = 'Failed to load options.'
            console.error(err)
        } finally {
            loading.value = false
        }
    }

    return {
        options,
        loading,
        error,
        fetchOptions,
    }
}