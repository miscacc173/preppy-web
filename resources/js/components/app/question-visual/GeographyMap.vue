<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT, MUTED } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <rect x="40" y="40" width="400" height="200" :fill="ACCENT" opacity="0.08" :stroke="STROKE" />
    <path d="M70,180 Q160,80 240,140 T420,90" fill="none" :stroke="ACCENT" stroke-width="2.5" />
    <circle cx="160" cy="130" r="5" :fill="STROKE" />
    <text x="170" y="125" font-size="11" :fill="STROKE">A</text>
    <circle cx="340" cy="110" r="5" :fill="STROKE" />
    <text x="350" y="105" font-size="11" :fill="STROKE">B</text>
    <text x="240" y="225" text-anchor="middle" font-size="11" :fill="MUTED">River course A → B</text>
    <g transform="translate(400,200)">
      <circle r="18" fill="none" :stroke="STROKE" />
      <polygon points="0,-14 4,0 0,14 -4,0" :fill="ACCENT" />
      <text x="0" y="-22" text-anchor="middle" font-size="9" :fill="STROKE">N</text>
    </g>
  </Frame>
</template>
