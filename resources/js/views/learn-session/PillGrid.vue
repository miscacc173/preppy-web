<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(defineProps<{ options: string[]; cols?: number }>(), { cols: 3 });
const model = defineModel<string>({ required: true });

const colsClass = computed(() => (props.cols === 4 ? "grid-cols-2 sm:grid-cols-4" : props.cols === 3 ? "grid-cols-2 sm:grid-cols-3" : "grid-cols-2"));
</script>
<template>
  <div :class="`grid ${colsClass} gap-2.5`">
    <button
      v-for="o in options"
      :key="o"
      :class="`px-3 min-h-12 text-[14px] font-medium rounded-lg border transition ${
        model === o ? 'bg-violet-light border-violet text-violet' : 'bg-bg-card border-border text-ink2 hover:border-violet'
      }`"
      @click="model = o"
    >
      {{ o }}
    </button>
  </div>
</template>
