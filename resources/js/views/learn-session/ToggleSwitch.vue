<script setup lang="ts">
withDefaults(defineProps<{ accent?: "violet" | "gold" }>(), { accent: "violet" });
const on = defineModel<boolean>("on", { required: true });
</script>
<template>
  <button
    role="switch"
    :aria-checked="on"
    :class="`relative w-10 h-6 rounded-full transition ${on ? (accent === 'gold' ? 'bg-gold' : 'bg-violet') : 'bg-bg-soft2 border border-border'}`"
    @click="on = !on"
  >
    <span :class="`absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow-sm transition-transform ${on ? 'translate-x-4' : ''}`" />
  </button>
</template>
