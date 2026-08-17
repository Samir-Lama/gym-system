import { defineStore } from "pinia";

export const useUIStore = defineStore("ui", {
    state: () => ({
        sidebarOpen: true,
        darkMode: false,
    }),

    actions: {
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
        },
    },
});