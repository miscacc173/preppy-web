<script setup lang="ts">
defineProps<{ active: boolean }>();
const emit = defineEmits<{ click: [] }>();
</script>
<template>
  <button
    :class="`px-3 py-1.5 rounded-full text-[12.5px] font-medium border transition ${active ? 'bg-blue text-white border-blue' : 'bg-bg-soft text-ink2 border-border hover:bg-bg-soft2'}`"
    @click="emit('click')"
  >
    <slot />
  </button>
</template>
