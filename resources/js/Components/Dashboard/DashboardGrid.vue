<script setup lang="ts">
import {
    Users,
    CreditCard,
    DollarSign,
    QrCode,
} from "lucide-vue-next";

import StatCard from "./StatCard.vue";

const stats = [
    {
        title: "Members",
        value: 248,
        description: "+12 this week",
        icon: Users,
    },
    {
        title: "Active Plans",
        value: 189,
        description: "Currently active",
        icon: CreditCard,
    },
    {
        title: "Monthly Revenue",
        value: "$12,430",
        description: "Current month",
        icon: DollarSign,
    },
    {
        title: "Today's Check-ins",
        value: 37,
        description: "Members today",
        icon: QrCode,
    },
];
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <StatCard
            v-for="stat in stats"
            :key="stat.title"
            :title="stat.title"
            :value="stat.value"
            :description="stat.description"
            :icon="stat.icon"
        />
    </div>
</template>