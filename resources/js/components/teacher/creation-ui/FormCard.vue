<script setup lang="ts">
defineProps<{ title: string; desc?: string }>();
</script>
<template>
  <div class="bg-bg-card border border-border rounded-[14px] p-5">
    <div class="mb-4">
      <h3 class="text-[14.5px] font-bold text-ink tracking-[-0.01em]">{{ title }}</h3>
      <p v-if="desc" class="text-[12.5px] text-ink3 font-light mt-1">{{ desc }}</p>
    </div>
    <div class="space-y-4"><slot /></div>
  </div>
</template>
