<script setup lang="ts">
defineProps<{ icon: unknown; label: string; to: string }>();
</script>
<template>
  <RouterLink :to="to" class="bg-bg-card border border-border rounded-[14px] p-3.5 flex flex-col items-start gap-2 hover:border-violet-300 hover:-translate-y-[1px] transition-all text-left">
    <div class="w-9 h-9 rounded-lg bg-violet-50 grid place-items-center">
      <component :is="icon" :size="16" class="text-violet-600" />
    </div>
    <span class="text-[13px] font-semibold text-ink leading-tight">{{ label }}</span>
  </RouterLink>
</template>
