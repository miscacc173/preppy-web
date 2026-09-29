<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{ points: number[] }>();
const w = 280;
const h = 56;
const pad = 4;

const min = computed(() => Math.min(...props.points) - 2);
const max = computed(() => Math.max(...props.points) + 2);
function sx(i: number) {
  return pad + (i * (w - pad * 2)) / (props.points.length - 1);
}
function sy(v: number) {
  return h - pad - ((v - min.value) / (max.value - min.value)) * (h - pad * 2);
}
const d = computed(() => props.points.map((v, i) => `${i === 0 ? "M" : "L"} ${sx(i)} ${sy(v)}`).join(" "));
const area = computed(() => `${d.value} L ${sx(props.points.length - 1)} ${h} L ${sx(0)} ${h} Z`);
</script>

<template>
  <svg :viewBox="`0 0 ${w} ${h}`" class="w-full h-14">
    <defs>
      <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="var(--mint)" stop-opacity="0.25" />
        <stop offset="100%" stop-color="var(--mint)" stop-opacity="0" />
      </linearGradient>
    </defs>
    <path :d="area" fill="url(#trendFill)" />
    <path :d="d" fill="none" stroke="var(--mint)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    <circle v-for="(v, i) in points" :key="i" :cx="sx(i)" :cy="sy(v)" :r="i === points.length - 1 ? 3 : 1.5" fill="var(--mint)" />
  </svg>
</template>
