<script setup lang="ts">
defineProps<{ icon: unknown; title: string }>();
</script>
<template>
  <section class="bg-bg-card border border-border rounded-2xl p-6">
    <div class="flex items-center gap-3 mb-4">
      <span class="w-9 h-9 rounded-lg grid place-items-center bg-blue-light text-blue">
        <component :is="icon" :size="16" />
      </span>
      <h3 class="text-[16px] font-semibold text-ink">{{ title }}</h3>
    </div>
    <div class="space-y-3"><slot /></div>
  </section>
</template>
