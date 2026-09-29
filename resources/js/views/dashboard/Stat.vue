<script setup lang="ts">
defineProps<{ label: string }>();
</script>
<template>
  <div class="bg-bg-soft rounded-md p-2 text-center">
    <div class="flex justify-center mb-0.5"><slot name="icon" /></div>
    <p class="font-mono text-[14px] font-medium text-ink leading-none"><slot /></p>
    <p class="text-[10px] text-ink4 mt-0.5">{{ label }}</p>
  </div>
</template>
