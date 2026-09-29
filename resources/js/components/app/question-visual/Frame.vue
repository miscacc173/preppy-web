<script setup lang="ts">
withDefaults(defineProps<{ vb?: string; class?: string }>(), { vb: "0 0 480 280" });
</script>
<template>
  <svg :viewBox="vb" :class="$props.class" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
    <rect x="0" y="0" width="100%" height="100%" fill="var(--bg-card)" />
    <slot />
  </svg>
</template>
