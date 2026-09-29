<script setup lang="ts">
defineProps<{ color: string; title: string; items: string[] }>();
</script>
<template>
  <div class="rounded-lg border border-border p-4 bg-bg-soft">
    <p :class="`text-[11px] font-semibold uppercase tracking-wider text-${color} mb-2`">{{ title }}</p>
    <ul class="space-y-1.5">
      <li v-for="i in items" :key="i" class="text-[12.5px] text-ink2">{{ i }}</li>
    </ul>
  </div>
</template>
