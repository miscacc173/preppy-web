<script setup lang="ts">
defineProps<{ label: string; options: string[] }>();
const model = defineModel<string>({ required: true });
</script>

<template>
  <label class="inline-flex items-center gap-1.5 text-[12.5px] text-ink3 bg-bg-card border border-border rounded-full pl-3 pr-2 py-1.5">
    <span class="font-medium text-ink2">{{ label }}:</span>
    <select v-model="model" class="bg-transparent text-[12.5px] font-semibold text-ink outline-none pr-1">
      <option v-for="o in options" :key="o">{{ o }}</option>
    </select>
  </label>
</template>
