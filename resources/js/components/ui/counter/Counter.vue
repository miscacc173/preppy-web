<script setup lang="ts">
/**
 * Rolling number counter. Animates once when scrolled into view.
 * Ported 1:1 from the React Counter.tsx (same IntersectionObserver + easing logic).
 */
import { onBeforeUnmount, onMounted, ref } from "vue";

const props = withDefaults(
  defineProps<{
    to: number;
    prefix?: string;
    suffix?: string;
    duration?: number;
    class?: string;
    formatter?: (val: number) => string;
  }>(),
  { prefix: "", suffix: "", duration: 1600 },
);

const el = ref<HTMLSpanElement | null>(null);
const val = ref(0);
let started = false;
let observer: IntersectionObserver | null = null;

onMounted(() => {
  if (!el.value) return;
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting && !started) {
          started = true;
          const start = performance.now();
          const tick = (now: number) => {
            const p = Math.min(1, (now - start) / props.duration);
            const eased = 1 - Math.pow(1 - p, 3);
            val.value = Math.round(props.to * eased);
            if (p < 1) requestAnimationFrame(tick);
          };
          requestAnimationFrame(tick);
        }
      });
    },
    { threshold: 0.4 },
  );
  observer.observe(el.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
  <span ref="el" :class="props.class">
    {{ prefix }}{{ formatter ? formatter(val) : val.toLocaleString() }}{{ suffix }}
  </span>
</template>
