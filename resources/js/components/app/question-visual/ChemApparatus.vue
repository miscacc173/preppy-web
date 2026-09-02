<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT, MUTED } from ".";
defineProps<{ class?: string }>();
</script>
<template>
  <Frame :class="$props.class">
    <path d="M180,120 L180,80 L220,80 L220,120 L260,210 L140,210 Z" :fill="ACCENT" opacity="0.15" :stroke="STROKE" stroke-width="2" />
    <line x1="180" y1="80" x2="220" y2="80" :stroke="STROKE" stroke-width="2" />
    <path d="M200,80 C200,40 360,40 360,90 L360,180" fill="none" :stroke="STROKE" stroke-width="2" />
    <rect x="350" y="180" width="22" height="60" rx="10" :fill="ACCENT" opacity="0.2" :stroke="STROKE" stroke-width="2" />
    <text x="200" y="240" text-anchor="middle" font-size="11" :fill="MUTED">Reaction flask + gas collection</text>
  </Frame>
</template>
