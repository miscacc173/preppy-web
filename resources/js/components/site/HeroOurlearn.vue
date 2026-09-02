<script setup lang="ts">
import { GraduationCap, Sparkles, MessageCircle, BarChart3, Linkedin, Instagram, Dribbble } from "lucide-vue-next";
import { Counter } from "@/components/ui/counter";
import { AnimatedButton } from "@/components/ui/animated-button";
import heroStudent from "@/assets/hero-student.png";

const stats = [
  { to: 124, suffix: "K+", l: "Students Training", formatter: (v: number) => (v / 10).toFixed(1) },
  { to: 200, suffix: "+", l: "Expert Examiners" },
  { to: 98, suffix: "%", l: "Grade Improvement" },
  { to: 6, suffix: " min", l: "To First Diagnosis" },
];
</script>

<template>
  <section class="bg-bg">
    <!-- Hero grid -->
    <div class="max-w-[1280px] mx-auto px-6 pt-10 pb-10 lg:pt-14 grid grid-cols-1 lg:grid-cols-[1fr_220px] gap-6 lg:gap-8 relative">
      <!-- Center hero -->
      <div class="relative text-center lg:text-left">
        <h1 class="font-display text-[44px] sm:text-[58px] lg:text-[72px] leading-[1.02] tracking-[-0.03em] text-ink">
          Think Like an
        </h1>
        <div class="my-3 lg:my-4 inline-block">
          <span class="inline-block bg-blue text-white font-display text-[36px] sm:text-[48px] lg:text-[60px] leading-none tracking-[-0.02em] px-6 py-3 rounded-full">
            A* Student.
          </span>
        </div>
        <p class="text-[15px] md:text-[16px] text-ink2 leading-[1.6] font-light mt-4 max-w-[560px] mx-auto lg:mx-0">
          Your AI study partner — trained on examiner reports, mark schemes, and top-scoring answers. Get instant feedback, understand what earns marks, and improve with every question.
        </p>
        <div class="hidden lg:flex items-center gap-3 mt-6">
          <AnimatedButton to="/demo" variant="violet" size="md">
            Start Your A* Journey <BarChart3 :size="14" />
          </AnimatedButton>
        </div>

        <!-- Photo composition -->
        <div class="relative mt-8 mx-auto lg:mx-0 max-w-[520px]">
          <div class="grid grid-cols-3 gap-3 absolute inset-0 -z-0">
            <div class="aspect-square rounded-[28px] bg-blue-light/60" />
            <div class="aspect-square rounded-[28px] bg-bg-soft2" />
            <div class="aspect-square rounded-[28px] bg-blue-light/60" />
          </div>
          <div class="relative z-10 grid grid-cols-3 gap-3">
            <div class="aspect-square rounded-[28px] bg-blue-light grid place-items-center">
              <MessageCircle :size="28" class="text-blue/60" />
            </div>
            <div class="aspect-square rounded-[28px] bg-bg-soft2 overflow-hidden grid place-items-end">
              <img :src="heroStudent" alt="AiStar Prep student" width="400" height="400" class="w-full h-full object-cover object-top" />
            </div>
            <div class="aspect-square rounded-[28px] bg-blue-light grid place-items-center">
              <GraduationCap :size="28" class="text-blue/60" />
            </div>
          </div>
        </div>
      </div>

      <!-- Right column -->
      <aside class="hidden lg:flex flex-col gap-6 pt-10 relative">
        <div class="flex items-start gap-3 justify-end relative">
          <div class="flex flex-col gap-3">
            <a href="#" class="text-ink2 hover:text-blue"><Dribbble :size="16" /></a>
            <a href="#" class="text-ink2 hover:text-blue"><Linkedin :size="16" /></a>
            <a href="#" class="text-ink2 hover:text-blue font-bold text-[14px]">Be</a>
            <a href="#" class="text-ink2 hover:text-blue"><Instagram :size="16" /></a>
          </div>
        </div>
        <Sparkles :size="22" class="text-blue self-end" />
        <div class="pt-3 border-t border-border text-right">
          <p class="font-display text-[20px] text-ink mt-4 tracking-[-0.02em]">Start Your<br />A* Journey</p>
        </div>
      </aside>

      <!-- Mobile-only condensed bottom -->
      <div class="lg:hidden flex flex-col items-center gap-3 mt-4">
        <p class="text-[14px] text-ink2 text-center max-w-[460px]">
          AI feedback trained on examiner reports and mark schemes — for O Level, A Level and IGCSE.
        </p>
        <AnimatedButton to="/demo" variant="violet" size="md">
          Start Your A* Journey <BarChart3 :size="14" />
        </AnimatedButton>
      </div>
    </div>

    <!-- Stats band -->
    <div class="bg-blue text-white">
      <div class="max-w-[1080px] mx-auto px-6 py-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
        <div v-for="(s, i) in stats" :key="s.l" :class="i < 3 ? 'md:border-r md:border-white/20' : ''">
          <Counter :to="s.to" :suffix="s.suffix" :formatter="s.formatter" class="font-display text-[26px] md:text-[32px] tracking-[-0.02em]" />
          <p class="text-[12px] text-white/80 mt-0.5">{{ s.l }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
