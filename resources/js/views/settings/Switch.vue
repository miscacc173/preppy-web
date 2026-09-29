<script setup lang="ts">
const on = defineModel<boolean>("on", { required: true });
</script>
<template>
  <button :class="`relative w-10 h-6 rounded-full transition shrink-0 ${on ? 'bg-blue' : 'bg-bg-soft2 border border-border'}`" @click="on = !on">
    <span :class="`absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform ${on ? 'translate-x-4' : ''}`" />
  </button>
</template>
