<script setup lang="ts">
withDefaults(defineProps<{ label: string; tone?: "amber" | "violet" | "mint"; icon?: unknown }>(), {});
const TONE: Record<string, string> = {
  amber: "bg-amber-50 border-amber-200",
  violet: "bg-violet-light border-violet/20",
  mint: "bg-mint-light border-mint/20",
};
</script>
<template>
  <div :class="`rounded-xl border ${tone ? TONE[tone] : 'bg-bg-soft/60 border-border'} p-3.5`">
    <div class="flex items-center gap-1.5 mb-1">
      <component :is="icon" v-if="icon" :size="12" class="text-ink3" />
      <p class="text-[11px] font-semibold uppercase tracking-wider text-ink3">{{ label }}</p>
    </div>
    <p class="text-[13.5px] font-semibold text-ink leading-snug break-words"><slot /></p>
  </div>
</template>
