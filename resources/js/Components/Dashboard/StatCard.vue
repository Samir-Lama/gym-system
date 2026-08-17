<script setup lang="ts">
import type { Component } from "vue";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";

interface Props {
    title: string;
    value: string | number;
    description?: string;
    icon?: Component;
}

defineProps<Props>();
</script>

<template>
    <Card class="shadow-sm transition-all hover:shadow-md">
        <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">
                {{ title }}
            </CardTitle>

            <component
                :is="icon"
                v-if="icon"
                class="h-5 w-5 text-muted-foreground"
            />
        </CardHeader>

        <CardContent>
            <div class="text-3xl font-bold">
                {{ value }}
            </div>

            <p
                v-if="description"
                class="mt-2 text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </CardContent>
    </Card>
</template>