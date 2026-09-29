<script setup lang="ts">
defineProps<{ active: boolean; comingSoon?: boolean }>();
const emit = defineEmits<{ click: [] }>();
</script>
<template>
  <button
    :disabled="comingSoon"
    :class="`inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium rounded-full border transition ${active ? 'bg-blue text-white border-blue' : comingSoon ? 'bg-bg-soft text-ink3 border-border cursor-not-allowed' : 'bg-bg-card text-ink2 border-border hover:border-blue-mid'}`"
    @click="emit('click')"
  >
    <slot />
    <span v-if="comingSoon" class="rounded-full bg-gold-light px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gold">Coming soon</span>
  </button>
</template>
