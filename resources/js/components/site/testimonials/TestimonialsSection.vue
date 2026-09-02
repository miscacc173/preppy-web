<script setup lang="ts">
import { TESTIMONIALS } from ".";
import TestimonialColumn from "./TestimonialColumn.vue";

const col1 = [TESTIMONIALS[0], TESTIMONIALS[3], TESTIMONIALS[6], TESTIMONIALS[9]];
const col2 = [TESTIMONIALS[1], TESTIMONIALS[4], TESTIMONIALS[7], TESTIMONIALS[10]];
const col3 = [TESTIMONIALS[2], TESTIMONIALS[5], TESTIMONIALS[8], TESTIMONIALS[11]];
</script>

<template>
  <section id="testimonials" class="relative px-6 py-20 lg:py-24 border-t border-border bg-bg overflow-hidden">
    <div class="max-w-[1160px] mx-auto">
      <div class="text-center max-w-[680px] mx-auto mb-14">
        <p class="label-mono mb-3">Testimonials</p>
        <h2 class="text-[32px] md:text-[42px] font-display font-bold text-ink tracking-[-0.03em] leading-[1.1] mb-4">
          Trusted by <span class="text-blue">Thousands of Students</span>
        </h2>
        <p class="text-[15px] text-ink2 leading-[1.65] font-light">
          See how AiStar Prep is helping students improve their grades, build
          confidence, and prepare smarter for their exams.
        </p>
      </div>

      <!-- Desktop: 3 columns · Tablet: 2 · Mobile: 1 -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <TestimonialColumn :items="col1" direction="up" :duration="45" />
        <div class="hidden md:block">
          <TestimonialColumn :items="col2" direction="down" :duration="55" />
        </div>
        <div class="hidden lg:block">
          <TestimonialColumn :items="col3" direction="up" :duration="50" />
        </div>
      </div>
    </div>
  </section>
</template>

<style>
@keyframes testimonials-scroll-up {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(-50%);
  }
}
@keyframes testimonials-scroll-down {
  from {
    transform: translateY(-50%);
  }
  to {
    transform: translateY(0);
  }
}
.animate-testimonials-up {
  animation: testimonials-scroll-up linear infinite;
}
.animate-testimonials-down {
  animation: testimonials-scroll-down linear infinite;
}
@media (prefers-reduced-motion: reduce) {
  .animate-testimonials-up,
  .animate-testimonials-down {
    animation: none;
  }
}
</style>
