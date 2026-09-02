<script setup lang="ts">
defineProps<{ icon: unknown; title: string; desc?: string; tint: string }>();
</script>
<template>
  <section class="bg-bg-card border border-border rounded-2xl p-6">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div class="flex items-start gap-3">
        <span :class="`w-9 h-9 rounded-lg grid place-items-center shrink-0 ${tint}`"><component :is="icon" :size="16" /></span>
        <div>
          <h3 class="text-[16px] font-semibold text-ink">{{ title }}</h3>
          <p v-if="desc" class="text-[12.5px] text-ink3 mt-0.5">{{ desc }}</p>
        </div>
      </div>
      <slot name="right" />
    </div>
    <div><slot /></div>
  </section>
</template>
