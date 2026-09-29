<script setup lang="ts">
defineProps<{ icon: unknown; eyebrow: string; title: string; subtitle?: string }>();
</script>
<template>
  <section class="bg-bg-card border border-border rounded-[20px] p-6 md:p-8 shadow-[0_4px_20px_rgba(15,23,42,0.04)]">
    <div class="flex items-start gap-4 mb-5">
      <div class="w-11 h-11 rounded-xl bg-blue-light grid place-items-center shrink-0">
        <component :is="icon" :size="20" class="text-blue" />
      </div>
      <div>
        <p class="label-mono text-blue-600 mb-1">{{ eyebrow }}</p>
        <h2 class="text-[19px] md:text-[21px] font-bold text-ink tracking-[-0.02em] leading-tight">{{ title }}</h2>
        <p v-if="subtitle" class="text-[13.5px] text-ink3 font-light mt-1">{{ subtitle }}</p>
      </div>
    </div>
    <div><slot /></div>
  </section>
</template>
