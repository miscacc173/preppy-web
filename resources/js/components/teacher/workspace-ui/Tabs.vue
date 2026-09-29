<script setup lang="ts">
defineProps<{ tabs: string[] }>();
const active = defineModel<string>("active", { required: true });
</script>

<template>
  <div class="flex items-center gap-1 border-b border-border mb-5 overflow-x-auto">
    <button
      v-for="t in tabs"
      :key="t"
      :class="`px-3 py-2 text-[13px] font-semibold whitespace-nowrap border-b-2 -mb-px transition ${
        t === active ? 'border-violet-600 text-violet-700' : 'border-transparent text-ink3 hover:text-ink2'
      }`"
      @click="active = t"
    >
      {{ t }}
    </button>
  </div>
</template>
