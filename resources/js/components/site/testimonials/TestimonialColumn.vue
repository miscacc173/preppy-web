<script setup lang="ts">
import type { Testimonial } from ".";
import TestimonialCard from "./TestimonialCard.vue";

const props = defineProps<{ items: Testimonial[]; direction: "up" | "down"; duration: number }>();
const animClass = props.direction === "up" ? "animate-testimonials-up" : "animate-testimonials-down";
</script>

<template>
  <div class="group relative h-[640px] overflow-hidden">
    <!-- fade masks -->
    <div class="pointer-events-none absolute inset-x-0 top-0 h-16 z-10 bg-gradient-to-b from-bg to-transparent" />
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-16 z-10 bg-gradient-to-t from-bg to-transparent" />
    <div :class="`flex flex-col gap-5 ${animClass} will-change-transform`" :style="{ animationDuration: `${duration}s` }">
      <div v-for="(t, i) in [...items, ...items]" :key="`${t.name}-${i}`">
        <TestimonialCard :t="t" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.group:hover .animate-testimonials-up,
.group:hover .animate-testimonials-down {
  animation-play-state: paused;
}
</style>
