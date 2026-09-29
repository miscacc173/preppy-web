<script setup lang="ts">
defineProps<{ items: string[] }>();
const model = defineModel<string>({ required: true });
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5">
    <button
      v-for="it in items"
      :key="it"
      :class="`px-4 py-3.5 text-[13.5px] font-medium rounded-full border transition text-center ${
        model === it ? 'bg-violet-light border-violet text-violet' : 'bg-bg-card border-border text-ink2 hover:border-violet hover:bg-violet-light/40'
      }`"
      @click="model = it"
    >
      {{ it }}
    </button>
  </div>
</template>
