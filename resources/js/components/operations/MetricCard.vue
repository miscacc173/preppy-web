<script setup lang="ts">
import type { Component } from "vue";

defineProps<{ label: string; value: string | number; hint?: string; icon: Component; tone?: "blue" | "mint" | "violet" | "gold" }>();
</script>

<template>
  <article class="rounded-2xl border border-border bg-bg-card p-5 shadow-sm">
    <div class="mb-5 flex items-start justify-between">
      <p class="text-[12px] font-semibold uppercase tracking-[0.09em] text-ink3">{{ label }}</p>
      <div class="grid h-9 w-9 place-items-center rounded-xl" :class="{
        'bg-blue-light text-blue': !tone || tone === 'blue',
        'bg-mint-light text-mint': tone === 'mint',
        'bg-violet-light text-violet': tone === 'violet',
        'bg-gold-light text-gold': tone === 'gold',
      }">
        <component :is="icon" :size="17" />
      </div>
    </div>
    <p class="text-[30px] font-bold tracking-[-0.04em] text-ink">{{ value }}</p>
    <p v-if="hint" class="mt-1 text-[12px] text-ink3">{{ hint }}</p>
  </article>
</template>
