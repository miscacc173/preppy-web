<script setup lang="ts">
withDefaults(defineProps<{ icon: unknown; title: string; desc?: string; tint?: "blue" | "violet" | "mint" | "gold" | "pink" }>(), { tint: "blue" });
const TINTS: Record<string, string> = {
  blue: "bg-blue-light text-blue",
  violet: "bg-violet-light text-violet",
  mint: "bg-mint-light text-mint",
  gold: "bg-gold-light text-gold",
  pink: "bg-pink-light text-pink",
};
</script>
<template>
  <section class="bg-bg-card border border-border rounded-2xl p-6">
    <div class="flex items-start gap-3 mb-4">
      <span :class="`w-9 h-9 rounded-lg grid place-items-center shrink-0 ${TINTS[tint]}`"><component :is="icon" :size="16" /></span>
      <div>
        <h3 class="text-[16px] font-semibold text-ink">{{ title }}</h3>
        <p v-if="desc" class="text-[12.5px] text-ink3 mt-0.5">{{ desc }}</p>
      </div>
    </div>
    <div><slot /></div>
  </section>
</template>
