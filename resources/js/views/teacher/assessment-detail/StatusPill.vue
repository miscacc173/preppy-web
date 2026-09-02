<script setup lang="ts">
defineProps<{ status: "draft" | "assigned" }>();
const MAP: Record<string, string> = { draft: "bg-amber-50 text-amber-700", assigned: "bg-emerald-50 text-emerald-700" };
</script>
<template>
  <span :class="`text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full ${MAP[status]}`">{{ status }}</span>
</template>
