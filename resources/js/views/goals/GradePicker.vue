<script setup lang="ts">
const GRADES = ["A*", "A", "B", "C"] as const;
const model = defineModel<string>({ required: true });
</script>
<template>
  <div class="inline-flex gap-1 bg-bg-soft rounded-lg p-1 border border-border">
    <button
      v-for="g in GRADES"
      :key="g"
      :class="`px-3 py-1 rounded-md text-[12.5px] font-semibold transition ${model === g ? 'bg-blue text-white' : 'text-ink2 hover:text-ink'}`"
      @click="model = g"
    >
      {{ g }}
    </button>
  </div>
</template>
