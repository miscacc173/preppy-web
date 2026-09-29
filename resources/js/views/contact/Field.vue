<script setup lang="ts">
import { AlertCircle } from "lucide-vue-next";
defineProps<{ id: string; label: string; error?: string }>();
</script>

<template>
  <div>
    <label :for="id" class="block text-[13px] font-medium text-ink mb-1.5">{{ label }}</label>
    <slot />
    <p v-if="error" class="mt-1.5 flex items-center gap-1 text-[12px] text-rose-600">
      <AlertCircle :size="12" />
      {{ error }}
    </p>
  </div>
</template>
