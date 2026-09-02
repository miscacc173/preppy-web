<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <line x1="60" y1="240" x2="440" y2="240" :stroke="STROKE" />
    <line x1="60" y1="240" x2="60" y2="40" :stroke="STROKE" />
    <text x="440" y="258" font-size="11" :fill="STROKE">V</text>
    <text x="48" y="50" font-size="11" :fill="STROKE">I</text>
    <path d="M60,240 C140,235 220,180 300,90 S420,55 440,50" fill="none" :stroke="ACCENT" stroke-width="2.5" />
    <text x="240" y="80" font-size="12" :fill="ACCENT">filament lamp</text>
  </Frame>
</template>
