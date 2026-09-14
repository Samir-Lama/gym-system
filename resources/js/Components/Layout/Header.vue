<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Bell, ChevronDown, LogOut, UserRound } from 'lucide-vue-next'

const page = usePage()
const user = page.props.auth.user

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>
    <header
        class="flex h-16 items-center justify-between border-b bg-white px-6"
    >
        <div>
            <h1 class="text-xl font-semibold">
                Dashboard
            </h1>
        </div>

        <div class="flex items-center gap-4">
            <button
                class="rounded-lg p-2 hover:bg-slate-100"
            >
                <Bell class="h-5 w-5" />
            </button>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors hover:bg-slate-100"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 font-medium text-slate-700"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>

                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-slate-900">
                                {{ user.name }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ user.email }}
                            </p>
                        </div>

                        <ChevronDown class="h-4 w-4 text-slate-500" />
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-52">
                    <DropdownMenuItem as-child>
                        <Link
                            :href="route('profile.edit')"
                            class="cursor-pointer"
                        >
                            <UserRound class="mr-2 h-4 w-4" />
                            Profile
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        class="cursor-pointer text-red-600 focus:text-red-600"
                        @click="logout"
                    >
                        <LogOut class="mr-2 h-4 w-4" />
                        Logout
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
