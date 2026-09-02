<script setup lang="ts">
withDefaults(defineProps<{ class?: string }>(), { class: "" });
</script>
<template>
  <div :class="`bg-bg-card border border-border rounded-[16px] p-5 md:p-6 shadow-[0_4px_20px_rgba(15,23,42,0.04)] ${$props.class}`">
    <slot />
  </div>
</template>
