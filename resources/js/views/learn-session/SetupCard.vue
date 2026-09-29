<script setup lang="ts">
defineProps<{ title: string; sub?: string }>();
</script>
<template>
  <section class="bg-bg-card border border-border rounded-2xl p-5 lg:p-6 reveal-up">
    <div class="mb-4">
      <h3 class="text-[15px] font-semibold text-ink tracking-[-0.01em]">{{ title }}</h3>
      <p v-if="sub" class="text-[12.5px] text-ink3 mt-0.5">{{ sub }}</p>
    </div>
    <div class="space-y-2"><slot /></div>
  </section>
</template>
