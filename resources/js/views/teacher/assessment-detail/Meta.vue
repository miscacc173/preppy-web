<script setup lang="ts">
defineProps<{ label: string; value: string }>();
</script>
<template>
  <span><span class="text-ink3 font-medium">{{ label }}:</span> <span class="font-semibold text-ink">{{ value }}</span></span>
</template>
