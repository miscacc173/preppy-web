<script setup lang="ts">
defineProps<{ options: { id: string; label: string; disabled?: boolean }[] }>();
const model = defineModel<string>({ required: true });
</script>
<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="o in options"
      :key="o.id"
      :disabled="o.disabled"
      :class="`px-3 py-1.5 rounded-full text-[12.5px] font-medium border transition ${
        model === o.id ? 'bg-blue text-white border-blue' : o.disabled ? 'bg-bg-soft text-ink4 border-border cursor-not-allowed' : 'bg-bg-soft text-ink2 border-border hover:bg-bg-soft2'
      }`"
      @click="model = o.id"
    >
      {{ o.label }}{{ o.disabled ? " (soon)" : "" }}
    </button>
  </div>
</template>
