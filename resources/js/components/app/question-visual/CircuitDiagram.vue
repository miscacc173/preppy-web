<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <rect x="80" y="60" width="320" height="160" fill="none" :stroke="STROKE" stroke-width="2" />
    <line x1="60" y1="130" x2="80" y2="130" :stroke="STROKE" stroke-width="2" />
    <line x1="60" y1="150" x2="80" y2="150" :stroke="STROKE" stroke-width="2" />
    <line x1="50" y1="120" x2="50" y2="160" :stroke="STROKE" stroke-width="3" />
    <line x1="70" y1="125" x2="70" y2="155" :stroke="STROKE" stroke-width="2" />
    <text x="30" y="145" font-size="11" :fill="STROKE">9 V</text>
    <rect x="200" y="50" width="80" height="20" :fill="ACCENT" opacity="0.2" :stroke="ACCENT" />
    <text x="240" y="42" text-anchor="middle" font-size="11" :fill="STROKE">R₁ = 220 Ω</text>
    <circle cx="240" cy="230" r="14" fill="none" :stroke="ACCENT" stroke-width="2" />
    <line x1="230" y1="220" x2="250" y2="240" :stroke="ACCENT" />
    <line x1="250" y1="220" x2="230" y2="240" :stroke="ACCENT" />
    <text x="270" y="234" font-size="11" :fill="STROKE">L</text>
  </Frame>
</template>
