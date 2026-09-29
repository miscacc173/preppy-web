<script setup lang="ts">
defineProps<{ label: string; value: string }>();
</script>
<template>
  <div class="flex items-start justify-between gap-3 py-2 border-b border-border last:border-0">
    <span class="text-[12.5px] font-semibold text-ink3 uppercase tracking-wider">{{ label }}</span>
    <span class="text-[13.5px] text-ink font-semibold text-right max-w-[70%]">{{ value }}</span>
  </div>
</template>
