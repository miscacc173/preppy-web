<script setup lang="ts">
withDefaults(defineProps<{ label: string; value: string; valueClass?: string }>(), { valueClass: "text-ink" });
</script>
<template>
  <div class="bg-bg-card/80 border border-border rounded-lg p-3">
    <p class="label-mono">{{ label }}</p>
    <p :class="`font-mono text-[22px] font-medium mt-1 ${valueClass}`">{{ value }}</p>
  </div>
</template>
