<script setup lang="ts">
withDefaults(
  defineProps<{ class?: string; side: "left" | "right" | "up"; label: string; title: string; desc: string }>(),
  { class: "" },
);
</script>

<template>
  <div :class="`absolute z-10 ${$props.class}`">
    <div class="relative w-[220px] bg-bg-card border border-border rounded-lg px-3 py-2.5 shadow-lift">
      <div class="flex items-center gap-2 mb-0.5">
        <span class="font-mono text-[10px] text-blue font-semibold">{{ label }}</span>
        <span class="h-px flex-1 bg-border" />
      </div>
      <p class="text-[12.5px] font-display text-ink tracking-[-0.01em] leading-snug">{{ title }}</p>
      <p class="text-[11px] text-ink3 leading-snug mt-0.5">{{ desc }}</p>
      <!-- Connector line -->
      <span v-if="side === 'right'" class="absolute top-1/2 -right-6 w-6 h-px bg-blue/50" />
      <span v-if="side === 'left'" class="absolute top-1/2 -left-6 w-6 h-px bg-blue/50" />
      <span v-if="side === 'up'" class="absolute left-1/2 -top-6 h-6 w-px bg-blue/50" />
    </div>
  </div>
</template>
