<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <polygon points="100,230 380,230 380,80" :fill="ACCENT" opacity="0.15" :stroke="STROKE" stroke-width="2" />
    <text x="240" y="248" text-anchor="middle" font-size="12" :fill="STROKE">4</text>
    <text x="395" y="160" font-size="12" :fill="STROKE">3</text>
    <text x="220" y="150" font-size="12" :fill="ACCENT">5</text>
    <rect x="365" y="215" width="15" height="15" fill="none" :stroke="STROKE" />
  </Frame>
</template>
