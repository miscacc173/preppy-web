<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT, MUTED } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <polygon points="80,220 60,260 100,260" :fill="MUTED" />
    <text x="80" y="275" text-anchor="middle" font-size="12" :fill="STROKE">P</text>
    <rect x="80" y="200" width="320" height="14" :fill="ACCENT" opacity="0.25" :stroke="ACCENT" />
    <circle cx="380" cy="207" r="4" :fill="ACCENT" />
    <text x="380" y="195" text-anchor="middle" font-size="12" :fill="STROKE">Q</text>
    <line x1="380" y1="214" x2="380" y2="255" :stroke="STROKE" stroke-width="2" marker-end="url(#arr)" />
    <text x="392" y="240" font-size="12" :fill="STROKE">10 N</text>
    <line x1="80" y1="120" x2="380" y2="120" :stroke="MUTED" stroke-dasharray="4 3" />
    <text x="230" y="113" text-anchor="middle" font-size="11" :fill="MUTED">3.0 m</text>
    <defs>
      <marker id="arr" marker-width="10" marker-height="10" ref-x="6" ref-y="5" orient="auto">
        <path d="M0,0 L6,5 L0,10 Z" :fill="STROKE" />
      </marker>
    </defs>
  </Frame>
</template>
