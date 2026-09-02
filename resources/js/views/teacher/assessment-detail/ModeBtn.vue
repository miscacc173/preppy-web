<script setup lang="ts">
defineProps<{ on: boolean }>();
const emit = defineEmits<{ click: [] }>();
</script>
<template>
  <button
    :class="`inline-flex items-center gap-1 text-[12px] font-semibold px-3 py-1.5 rounded-full transition ${on ? 'bg-bg-card text-violet-700 shadow-sm' : 'text-ink3 hover:text-ink'}`"
    @click="emit('click')"
  >
    <slot />
  </button>
</template>
