<script setup lang="ts">
defineProps<{ tone: "blue" | "mint" | "violet" | "gold" }>();
const MAP: Record<string, string> = {
  blue: "bg-blue-light text-blue border-blue/20",
  mint: "bg-mint-light text-mint border-mint/20",
  violet: "bg-violet-light text-violet border-violet/20",
  gold: "bg-gold-light text-gold border-gold/30",
};
</script>
<template>
  <span :class="`inline-flex items-center px-2.5 py-1 rounded-full border text-[11.5px] font-semibold ${MAP[tone]}`">
    <slot />
  </span>
</template>
