<script setup lang="ts">
withDefaults(defineProps<{ label: string; value: string; valueClass?: string }>(), { valueClass: "text-ink" });
</script>
<template>
  <div class="flex flex-col">
    <span class="text-[10px] text-ink4 uppercase font-mono tracking-wider">{{ label }}</span>
    <span :class="`text-[14px] font-mono font-semibold inline-flex items-center gap-1 ${valueClass}`">
      <slot name="icon" /> {{ value }}
    </span>
  </div>
</template>
