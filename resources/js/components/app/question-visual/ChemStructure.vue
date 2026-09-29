<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT, MUTED } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <g :stroke="STROKE" stroke-width="2" fill="none">
      <line x1="120" y1="180" x2="180" y2="140" />
      <line x1="180" y1="140" x2="240" y2="180" />
      <line x1="240" y1="180" x2="300" y2="140" />
    </g>
    <text x="300" y="135" font-size="16" :fill="ACCENT" font-weight="600">OH</text>
    <text x="200" y="240" text-anchor="middle" font-size="12" :fill="MUTED">Ethanol (C₂H₅OH)</text>
  </Frame>
</template>
