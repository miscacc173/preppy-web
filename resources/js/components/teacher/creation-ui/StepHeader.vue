<script setup lang="ts">
defineProps<{ eyebrow?: string; title: string; subtitle?: string }>();
</script>
<template>
  <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-6">
    <div class="min-w-0">
      <p v-if="eyebrow" class="label-mono text-violet-600 mb-1.5">{{ eyebrow }}</p>
      <h1 class="text-[22px] md:text-[26px] font-bold text-ink tracking-[-0.02em] leading-tight">{{ title }}</h1>
      <p v-if="subtitle" class="text-[13.5px] text-ink2 font-light mt-1.5 max-w-[640px] leading-[1.6]">{{ subtitle }}</p>
    </div>
    <slot name="right" />
  </div>
</template>
