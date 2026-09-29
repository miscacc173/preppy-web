<script setup lang="ts">
defineProps<{ placeholder: string }>();
const model = defineModel<string>({ required: true });
</script>

<template>
  <input
    v-model="model"
    :placeholder="placeholder"
    class="flex-1 min-w-[180px] bg-bg-card border border-border rounded-full px-4 py-1.5 text-[13px] text-ink placeholder:text-ink3 outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100 transition"
  />
</template>
