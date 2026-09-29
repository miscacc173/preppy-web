<script setup lang="ts">
withDefaults(defineProps<{ label: string; type?: string }>(), { type: "text" });
const model = defineModel<string>({ required: true });
</script>
<template>
  <label class="block">
    <div class="text-[11px] font-semibold text-ink3 mb-0.5">{{ label }}</div>
    <input v-model="model" :type="type" class="w-full bg-bg-card border border-border rounded-md px-2 py-1.5 text-[12.5px] text-ink outline-none focus:border-violet-300" />
  </label>
</template>
