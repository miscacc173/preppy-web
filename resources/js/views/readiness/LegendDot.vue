<script setup lang="ts">
defineProps<{ color: string; label: string }>();
</script>
<template>
  <span class="inline-flex items-center gap-1">
    <span class="w-2 h-2 rounded-full" :style="{ background: color }" />
    {{ label }}
  </span>
</template>
