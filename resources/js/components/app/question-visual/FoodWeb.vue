<script setup lang="ts">
import Frame from "./Frame.vue";
import { MUTED, ACCENT, STROKE } from ".";
defineProps<{ class?: string }>();

const nodes = [
  { x: 120, y: 230, label: "Grass" },
  { x: 240, y: 230, label: "Plant" },
  { x: 360, y: 230, label: "Algae" },
  { x: 240, y: 140, label: "Rabbit" },
  { x: 240, y: 60, label: "Fox" },
];
</script>
<template>
  <Frame :class="$props.class">
    <line x1="120" y1="220" x2="240" y2="140" :stroke="MUTED" />
    <line x1="240" y1="220" x2="240" y2="140" :stroke="MUTED" />
    <line x1="360" y1="220" x2="240" y2="140" :stroke="MUTED" />
    <line x1="240" y1="140" x2="240" y2="60" :stroke="MUTED" />
    <g v-for="n in nodes" :key="n.label">
      <circle :cx="n.x" :cy="n.y" r="22" :fill="ACCENT" opacity="0.2" :stroke="ACCENT" stroke-width="1.5" />
      <text :x="n.x" :y="n.y + 4" text-anchor="middle" font-size="10" :fill="STROKE">{{ n.label }}</text>
    </g>
  </Frame>
</template>
