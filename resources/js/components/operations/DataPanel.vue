<script setup lang="ts">
defineProps<{ title: string; description?: string; loading?: boolean; empty?: boolean }>();
</script>

<template>
  <section class="overflow-hidden rounded-2xl border border-border bg-bg-card shadow-sm">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
      <div>
        <h2 class="text-[15px] font-semibold text-ink">{{ title }}</h2>
        <p v-if="description" class="mt-0.5 text-[12px] text-ink3">{{ description }}</p>
      </div>
      <slot name="actions" />
    </header>
    <div v-if="loading" class="space-y-3 p-5" role="status" aria-label="Loading live data">
      <div v-for="row in 5" :key="row" class="grid grid-cols-[44px_1.4fr_1fr_.7fr] items-center gap-4 rounded-xl border border-border/70 p-3">
        <span class="ops-shimmer h-10 w-10 rounded-xl" />
        <span class="space-y-2"><span class="ops-shimmer block h-3 w-3/4 rounded-full" /><span class="ops-shimmer block h-2.5 w-1/2 rounded-full" /></span>
        <span class="ops-shimmer h-3 rounded-full" />
        <span class="ops-shimmer h-7 rounded-lg" />
      </div>
      <span class="sr-only">Loading live data…</span>
    </div>
    <div v-else-if="empty" class="grid min-h-48 place-items-center px-6 text-center">
      <div>
        <p class="text-[14px] font-medium text-ink2">No records found</p>
        <p class="mt-1 text-[12px] text-ink3">New records will appear here when they are available.</p>
      </div>
    </div>
    <slot v-else />
  </section>
</template>

<style scoped>
.ops-shimmer { position: relative; overflow: hidden; background: color-mix(in srgb, var(--color-border, #e5e7eb) 72%, transparent); }
.ops-shimmer::after { content: ""; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.7), transparent); animation: ops-shimmer 1.35s infinite; }
@keyframes ops-shimmer { to { transform: translateX(100%); } }
@media (prefers-reduced-motion: reduce) { .ops-shimmer::after { animation: none; } }
</style>
