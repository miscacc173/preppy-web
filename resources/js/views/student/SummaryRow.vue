<script setup lang="ts">
defineProps<{ label: string }>();
</script>
<template>
  <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-1 sm:gap-4 py-3">
    <dt class="text-[12.5px] font-semibold uppercase tracking-wider text-ink3">{{ label }}</dt>
    <dd class="text-[13.5px] text-ink2 font-light"><slot /></dd>
  </div>
</template>
