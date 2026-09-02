<script setup lang="ts">
defineProps<{ title: string; disabled?: boolean }>();
const emit = defineEmits<{ click: [] }>();
</script>
<template>
  <button
    :title="title"
    :disabled="disabled"
    class="w-7 h-7 rounded-md border border-border bg-bg-card grid place-items-center text-ink2 hover:border-violet-300 hover:text-violet-700 disabled:opacity-40 disabled:pointer-events-none"
    @click="emit('click')"
  >
    <slot />
  </button>
</template>
