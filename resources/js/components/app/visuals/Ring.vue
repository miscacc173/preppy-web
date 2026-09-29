<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{ value: number; size?: number; color?: string; label?: string }>(),
  { size: 140, color: "var(--blue)" },
);

const stroke = 10;
const r = computed(() => (props.size - stroke) / 2);
const c = computed(() => 2 * Math.PI * r.value);
const off = computed(() => c.value - (props.value / 100) * c.value);
</script>

<template>
  <div class="relative grid place-items-center" :style="{ width: `${size}px`, height: `${size}px` }">
    <svg :width="size" :height="size" class="-rotate-90">
      <circle :cx="size / 2" :cy="size / 2" :r="r" stroke="var(--bg-soft2)" :stroke-width="stroke" fill="none" />
      <circle
        :cx="size / 2"
        :cy="size / 2"
        :r="r"
        :stroke="color"
        :stroke-width="stroke"
        fill="none"
        :stroke-dasharray="c"
        :stroke-dashoffset="off"
        stroke-linecap="round"
        style="transition: stroke-dashoffset 1s cubic-bezier(.2,.8,.2,1)"
      />
    </svg>
    <div class="absolute inset-0 grid place-items-center text-center">
      <div>
        <div class="font-mono text-[28px] font-medium text-ink leading-none">{{ value }}<span class="text-[14px] text-ink3">%</span></div>
        <div v-if="label" class="label-mono mt-1.5">{{ label }}</div>
      </div>
    </div>
  </div>
</template>
