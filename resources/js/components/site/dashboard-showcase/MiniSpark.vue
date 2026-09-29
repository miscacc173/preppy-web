<script setup lang="ts">
const pts = [38, 44, 46, 50, 55, 58, 62, 66, 68, 70, 73, 74];
const max = 80;
const w = 180;
const h = 40;
const step = w / (pts.length - 1);
const path = pts.map((v, i) => `${i === 0 ? "M" : "L"} ${i * step},${h - (v / max) * h}`).join(" ");
const area = `${path} L ${w},${h} L 0,${h} Z`;
</script>

<template>
  <svg :viewBox="`0 0 ${w} ${h}`" class="w-full h-10">
    <defs>
      <linearGradient id="mini-spark" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0%" stop-color="var(--color-blue, #3b82f6)" stop-opacity="0.35" />
        <stop offset="100%" stop-color="var(--color-blue, #3b82f6)" stop-opacity="0" />
      </linearGradient>
    </defs>
    <path :d="area" fill="url(#mini-spark)" />
    <path :d="path" fill="none" stroke="var(--color-blue, #3b82f6)" stroke-width="1.5" />
  </svg>
</template>
