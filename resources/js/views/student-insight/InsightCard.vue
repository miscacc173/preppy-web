<script setup lang="ts">
defineProps<{ icon: unknown; tone: "mint" | "amber"; label: string; title: string; body: string }>();
const CLS: Record<string, { bg: string; ic: string; tint: string }> = {
  mint: { bg: "bg-mint-light", ic: "text-mint", tint: "text-mint" },
  amber: { bg: "bg-amber-50", ic: "text-amber-600", tint: "text-amber-700" },
};
</script>
<template>
  <div class="p-5 rounded-2xl border border-border bg-bg-soft/40">
    <div class="flex items-center gap-2 mb-3">
      <div :class="`w-8 h-8 rounded-lg ${CLS[tone].bg} grid place-items-center`">
        <component :is="icon" :size="15" :class="CLS[tone].ic" />
      </div>
      <p :class="`label-mono ${CLS[tone].tint}`">{{ label }}</p>
    </div>
    <p class="text-[15px] font-semibold text-ink leading-snug mb-1.5">{{ title }}</p>
    <p class="text-[13px] text-ink2 font-light leading-[1.6]">{{ body }}</p>
  </div>
</template>
