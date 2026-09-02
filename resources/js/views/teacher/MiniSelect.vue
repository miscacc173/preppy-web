<script setup lang="ts">
defineProps<{ label: string; options: string[] }>();
const model = defineModel<string>({ required: true });
</script>
<template>
  <label class="block">
    <div class="text-[11px] font-semibold text-ink3 mb-0.5">{{ label }}</div>
    <select v-model="model" class="w-full bg-bg-card border border-border rounded-md px-2 py-1.5 text-[12.5px] text-ink outline-none focus:border-violet-300">
      <option v-for="o in options" :key="o">{{ o }}</option>
    </select>
  </label>
</template>
