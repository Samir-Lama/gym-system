<script setup lang="ts">
import { CheckCircle2, XCircle, Info, TriangleAlert, Loader2 } from 'lucide-vue-next'
import { Toaster as Sonner } from 'vue-sonner'
import 'vue-sonner/style.css'
</script>

<template>
  <Sonner position="top-right" :expand="false" :rich-colors="false" :close-button="true" :toast-options="{
    classes: {
      toast:
        'group rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg',
      title:
        'text-sm font-semibold text-slate-900',
      description:
        'text-xs text-slate-500',
      actionButton:
        'bg-slate-900 text-white',
      cancelButton:
        'bg-slate-100 text-slate-700',
      closeButton:
        'border-slate-200 bg-white text-slate-500 hover:bg-slate-100',
      success:
        'border-emerald-200 bg-emerald-50',
      error:
        'border-red-200 bg-red-50',
      warning:
        'border-amber-200 bg-amber-50',
      info:
        'border-blue-200 bg-blue-50',
    },
  }">
    <template #success-icon>
      <CheckCircle2 class="h-5 w-5 text-emerald-600" />
    </template>

    <template #error-icon>
      <XCircle class="h-5 w-5 text-red-600" />
    </template>

    <template #warning-icon>
      <TriangleAlert class="h-5 w-5 text-amber-600" />
    </template>

    <template #info-icon>
      <Info class="h-5 w-5 text-blue-600" />
    </template>

    <template #loading-icon>
      <Loader2 class="h-5 w-5 animate-spin text-slate-500" />
    </template>
  </Sonner>
</template>