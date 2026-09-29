<script setup lang="ts">
import { Check } from "lucide-vue-next";
defineProps<{ active: boolean }>();
const emit = defineEmits<{ click: [] }>();
</script>
<template>
  <button
    type="button"
    :class="`inline-flex items-center gap-1.5 text-[12.5px] font-semibold px-3 py-1.5 rounded-full border transition ${
      active ? 'bg-violet-600 text-white border-violet-600' : 'bg-bg-card text-ink2 border-border hover:border-violet-300'
    }`"
    @click="emit('click')"
  >
    <Check v-if="active" :size="12" />
    <slot />
  </button>
</template>
