<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <ellipse cx="240" cy="140" rx="180" ry="100" :fill="ACCENT" opacity="0.1" :stroke="STROKE" stroke-width="2" />
    <circle cx="220" cy="140" r="34" :fill="ACCENT" opacity="0.35" :stroke="STROKE" stroke-width="1.5" />
    <circle cx="220" cy="140" r="10" :fill="STROKE" />
    <ellipse cx="320" cy="120" rx="22" ry="10" fill="none" :stroke="STROKE" />
    <ellipse cx="170" cy="190" rx="18" ry="8" fill="none" :stroke="STROKE" />
    <text x="220" y="195" text-anchor="middle" font-size="11" :fill="STROKE">Nucleus</text>
    <text x="320" y="108" text-anchor="middle" font-size="11" :fill="STROKE">Mitochondrion</text>
    <text x="170" y="178" text-anchor="middle" font-size="11" :fill="STROKE">Ribosome</text>
  </Frame>
</template>
