<script setup lang="ts">
defineProps<{ label: string; hint?: string }>();
</script>
<template>
  <label class="block">
    <div class="text-[12.5px] font-semibold text-ink2 mb-1.5">{{ label }}</div>
    <slot />
    <div v-if="hint" class="text-[11.5px] text-ink3 font-light mt-1">{{ hint }}</div>
  </label>
</template>
