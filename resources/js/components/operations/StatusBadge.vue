<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{ value?: string }>();
const label = computed(() => (props.value || "unknown").replaceAll("_", " "));
const tone = computed(() => {
  const value = (props.value || "").toLowerCase();
  if (["active", "paid", "completed", "accepted", "open"].includes(value)) return "bg-mint-light text-mint";
  if (["pending", "trialing", "processing", "partially paid", "coming soon"].includes(value.replaceAll("_", " "))) return "bg-gold-light text-gold";
  if (["suspended", "disabled", "failed", "closed", "cancelled", "canceled"].includes(value)) return "bg-pink-light text-pink";
  return "bg-blue-light text-blue";
});
</script>

<template>
  <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize" :class="tone">{{ label }}</span>
</template>
