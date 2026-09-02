<script setup lang="ts">
defineProps<{ icon: unknown; title: string }>();
</script>
<template>
  <div class="flex items-center gap-2.5 p-3 rounded-xl border border-border bg-bg-card">
    <div class="w-8 h-8 rounded-lg bg-bg-soft grid place-items-center shrink-0">
      <component :is="icon" :size="14" class="text-violet" />
    </div>
    <span class="text-[12.5px] font-semibold text-ink leading-tight">{{ title }}</span>
  </div>
</template>
