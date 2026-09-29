<script setup lang="ts">
defineProps<{ icon: unknown; label: string; value: string }>();
</script>
<template>
  <div class="rounded-lg border border-border bg-bg-soft/60 p-3">
    <div class="flex items-center gap-1.5 text-ink3 mb-1">
      <component :is="icon" :size="12" />
      <span class="text-[10.5px] font-semibold uppercase tracking-wider">{{ label }}</span>
    </div>
    <p class="text-[13px] font-semibold text-ink truncate">{{ value }}</p>
  </div>
</template>
