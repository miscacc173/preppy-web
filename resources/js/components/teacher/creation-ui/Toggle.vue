<script setup lang="ts">
defineProps<{ label: string; desc?: string }>();
const checked = defineModel<boolean>({ required: true });
</script>
<template>
  <button
    type="button"
    class="w-full flex items-start justify-between gap-3 text-left bg-bg-card border border-border rounded-lg p-3 hover:border-violet-300 transition"
    @click="checked = !checked"
  >
    <div class="min-w-0">
      <div class="text-[13px] font-semibold text-ink">{{ label }}</div>
      <div v-if="desc" class="text-[12px] text-ink3 font-light mt-0.5">{{ desc }}</div>
    </div>
    <div :class="`shrink-0 w-9 h-5 rounded-full relative transition ${checked ? 'bg-violet-600' : 'bg-bg-soft border border-border'}`">
      <span :class="`absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition ${checked ? 'left-[18px]' : 'left-0.5'}`" />
    </div>
  </button>
</template>
