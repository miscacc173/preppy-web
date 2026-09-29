<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <line x1="40" y1="240" x2="440" y2="240" :stroke="STROKE" />
    <path d="M40,240 Q240,40 440,240" fill="none" :stroke="ACCENT" stroke-width="2.5" stroke-dasharray="6 4" />
    <circle cx="40" cy="240" r="6" :fill="ACCENT" />
    <line x1="40" y1="240" x2="100" y2="180" :stroke="STROKE" marker-end="url(#arr2)" />
    <text x="105" y="175" font-size="11" :fill="STROKE">u = 25 m/s</text>
    <text x="60" y="225" font-size="11" :fill="STROKE">θ = 45°</text>
    <defs>
      <marker id="arr2" marker-width="10" marker-height="10" ref-x="6" ref-y="5" orient="auto">
        <path d="M0,0 L6,5 L0,10 Z" :fill="STROKE" />
      </marker>
    </defs>
  </Frame>
</template>
