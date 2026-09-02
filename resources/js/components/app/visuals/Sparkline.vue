<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{ points: number[]; color?: string; h?: number }>(),
  { color: "var(--blue)", h: 40 },
);

const w = 200;

const d = computed(() => {
  const max = Math.max(...props.points);
  const min = Math.min(...props.points);
  const range = max - min || 1;
  const step = w / (props.points.length - 1);
  return props.points
    .map((p, i) => `${i === 0 ? "M" : "L"} ${i * step} ${props.h - ((p - min) / range) * (props.h - 4) - 2}`)
    .join(" ");
});

const area = computed(() => `${d.value} L ${w} ${props.h} L 0 ${props.h} Z`);
const gradientId = computed(() => `spark-${props.color.replace(/[^a-zA-Z0-9-]/g, "")}`);
</script>

<template>
  <svg :viewBox="`0 0 ${w} ${h}`" class="w-full h-auto" preserveAspectRatio="none">
    <defs>
      <linearGradient :id="gradientId" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0%" :stop-color="color" stop-opacity="0.18" />
        <stop offset="100%" :stop-color="color" stop-opacity="0" />
      </linearGradient>
    </defs>
    <path :d="area" :fill="`url(#${gradientId})`" />
    <path :d="d" fill="none" :stroke="color" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" />
  </svg>
</template>
