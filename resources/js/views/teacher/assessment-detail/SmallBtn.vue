<script setup lang="ts">
withDefaults(defineProps<{ danger?: boolean }>(), { danger: false });
const emit = defineEmits<{ click: [] }>();
</script>
<template>
  <button
    :class="`inline-flex items-center gap-1 text-[11.5px] font-semibold px-2 py-1 rounded-md border transition ${
      danger ? 'border-border text-red-600 hover:bg-red-50 hover:border-red-200' : 'border-border text-ink2 hover:border-violet-300 hover:text-violet-700'
    }`"
    @click="emit('click')"
  >
    <slot />
  </button>
</template>
