import type { Component } from 'vue'

export interface StatCardData {
    title: string
    value: string | number
    description?: string
    icon?: Component
}