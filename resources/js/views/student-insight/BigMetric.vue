<script setup lang="ts">
defineProps<{ icon: unknown; tone: "blue" | "violet" | "gold"; label: string; value: string; sub: string }>();
const MAP: Record<string, { bg: string; ic: string }> = {
  blue: { bg: "bg-blue-light", ic: "text-blue" },
  violet: { bg: "bg-violet-light", ic: "text-violet" },
  gold: { bg: "bg-gold-light", ic: "text-gold" },
};
</script>
<template>
  <div class="bg-bg-card border border-border rounded-[20px] p-5 shadow-[0_4px_20px_rgba(15,23,42,0.04)]">
    <div :class="`w-10 h-10 rounded-xl ${MAP[tone].bg} grid place-items-center mb-3`">
      <component :is="icon" :size="18" :class="MAP[tone].ic" />
    </div>
    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink3 mb-1">{{ label }}</p>
    <p class="text-[24px] font-bold text-ink tracking-[-0.02em] leading-tight">{{ value }}</p>
    <p class="text-[12.5px] text-ink3 font-light mt-1">{{ sub }}</p>
  </div>
</template>
