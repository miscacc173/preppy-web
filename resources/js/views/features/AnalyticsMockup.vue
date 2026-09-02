<script setup lang="ts">
import { onMounted, ref } from "vue";
import { TrendingUp, Zap, Target, ChevronRight } from "lucide-vue-next";

const pts = [42, 48, 45, 55, 58, 62, 66, 70, 73, 74];
const w = 320;
const h = 90;
const step = w / (pts.length - 1);
const path = pts.map((v, i) => `${i === 0 ? "M" : "L"} ${i * step},${h - (v / 100) * h}`).join(" ");
const area = `${path} L ${w},${h} L 0,${h} Z`;
const pathLen = 500; // generous static estimate for stroke-dasharray draw-in

const CARDS = [
  { l: "Strengths", v: "Kinematics · Waves", i: Zap, c: "mint" as const },
  { l: "Focus areas", v: "Organic mech.", i: Target, c: "gold" as const },
];

const mounted = ref(false);
onMounted(() => requestAnimationFrame(() => (mounted.value = true)));
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink4">Readiness trend</p>
        <p class="font-mono text-[28px] text-ink leading-none tracking-[-0.02em] mt-1">74<span class="text-[16px] text-ink3">%</span></p>
      </div>
      <span class="inline-flex items-center gap-1 text-[11px] font-mono text-mint bg-mint-light px-2 py-1 rounded-md">
        <TrendingUp :size="11" /> +18%
      </span>
    </div>
    <div class="rounded-xl border border-border bg-bg p-3">
      <svg :viewBox="`0 0 ${w} ${h}`" class="w-full h-24">
        <defs>
          <linearGradient id="feat-spark" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="var(--blue)" stop-opacity="0.35" />
            <stop offset="100%" stop-color="var(--blue)" stop-opacity="0" />
          </linearGradient>
        </defs>
        <path :d="area" fill="url(#feat-spark)" class="transition-opacity duration-1000" :style="{ opacity: mounted ? 1 : 0, transitionDelay: '0.4s' }" />
        <path
          :d="path"
          fill="none"
          stroke="var(--blue)"
          stroke-width="2"
          stroke-linecap="round"
          :stroke-dasharray="pathLen"
          class="transition-[stroke-dashoffset] duration-[1200ms] ease-[cubic-bezier(0.22,1,0.36,1)]"
          :style="{ strokeDashoffset: mounted ? 0 : pathLen }"
        />
      </svg>
    </div>
    <div class="grid grid-cols-2 gap-2.5">
      <div v-for="x in CARDS" :key="x.l" class="rounded-lg border border-border bg-bg p-2.5">
        <div class="flex items-center gap-1.5 mb-1">
          <component :is="x.i" :size="11" :class="x.c === 'mint' ? 'text-mint' : 'text-gold'" />
          <span class="text-[9.5px] uppercase tracking-[0.1em] text-ink4 font-semibold">{{ x.l }}</span>
        </div>
        <p class="text-[11.5px] text-ink font-medium">{{ x.v }}</p>
      </div>
    </div>
    <div class="flex items-center justify-between rounded-lg bg-bg-soft border border-border px-3 py-2">
      <span class="text-[11px] text-ink3">Next milestone</span>
      <span class="font-mono text-[11px] text-blue font-semibold flex items-center gap-1">
        Grade A* <ChevronRight :size="11" />
      </span>
    </div>
  </div>
</template>
