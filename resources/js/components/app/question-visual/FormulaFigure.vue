<script setup lang="ts">
import Frame from "./Frame.vue";
import { STROKE, ACCENT } from ".";
defineProps<{ text: string; class?: string }>();
</script>
<template>
  <Frame vb="0 0 480 160" :class="$props.class">
    <rect x="40" y="40" width="400" height="80" rx="10" :fill="ACCENT" opacity="0.12" :stroke="ACCENT" />
    <text x="240" y="92" text-anchor="middle" font-size="22" font-family="ui-monospace, SFMono-Regular, Menlo, monospace" :fill="STROKE">
      {{ text || "—" }}
    </text>
  </Frame>
</template>
