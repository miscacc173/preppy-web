<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";
defineProps<{ class?: string }>();

const pts: string[] = [];
for (let x = -4; x <= 4; x += 0.2) {
  const px = 240 + x * 36;
  const py = 240 - x * x * 14;
  pts.push(`${px},${py}`);
}
const points = pts.join(" ");
</script>
<template>
  <Frame :class="$props.class">
    <line x1="40" y1="240" x2="440" y2="240" :stroke="STROKE" />
    <line x1="240" y1="40" x2="240" y2="260" :stroke="STROKE" />
    <text x="445" y="245" font-size="11" :fill="STROKE">x</text>
    <text x="248" y="48" font-size="11" :fill="STROKE">y</text>
    <polyline :points="points" fill="none" :stroke="ACCENT" stroke-width="2.5" />
    <text x="320" y="100" font-size="12" :fill="ACCENT">y = x²</text>
  </Frame>
</template>
