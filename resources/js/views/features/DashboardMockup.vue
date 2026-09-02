<script setup lang="ts">
/**
 * The source animates each subject bar's width via framer-motion's
 * `whileInView`. Simplified here to animate on mount (this mockup only
 * renders once its parent <Reveal> has already scrolled into view, so the
 * visible effect is the same) using a CSS transition gated by a `mounted` ref.
 */
import { onMounted, ref } from "vue";
import { Flame, Trophy, Target } from "lucide-vue-next";

const STATS = [
  { i: Flame, l: "Streak", v: "14d", c: "text-gold", bg: "bg-gold-light" },
  { i: Trophy, l: "Grade", v: "A", c: "text-violet", bg: "bg-violet-light" },
  { i: Target, l: "Ready", v: "74%", c: "text-mint", bg: "bg-mint-light" },
];

const SUBJECTS = [
  { n: "Physics", s: 71, g: "A", c: "bg-mint" },
  { n: "Chemistry", s: 64, g: "B", c: "bg-gold" },
  { n: "Maths", s: 82, g: "A*", c: "bg-blue" },
];

const mounted = ref(false);
onMounted(() => {
  requestAnimationFrame(() => (mounted.value = true));
});
</script>

<template>
  <div class="space-y-4">
    <div class="grid grid-cols-3 gap-2.5">
      <div v-for="s in STATS" :key="s.l" class="rounded-xl border border-border bg-bg p-3">
        <div class="flex items-center gap-1.5 mb-1.5">
          <span :class="`grid place-items-center w-5 h-5 rounded-md ${s.bg}`">
            <component :is="s.i" :size="11" :class="s.c" />
          </span>
          <span class="text-[9.5px] uppercase tracking-[0.1em] text-ink4 font-semibold">{{ s.l }}</span>
        </div>
        <div class="font-mono text-[22px] text-ink leading-none tracking-[-0.02em]">{{ s.v }}</div>
      </div>
    </div>
    <div>
      <p class="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink4 mb-2">Subjects</p>
      <div class="space-y-2">
        <div v-for="s in SUBJECTS" :key="s.n" class="flex items-center gap-3 rounded-lg border border-border bg-bg p-2.5">
          <span class="text-[12px] font-display text-ink w-20">{{ s.n }}</span>
          <div class="flex-1 h-1.5 rounded-full bg-bg-soft2 overflow-hidden">
            <div
              :class="`h-full rounded-full transition-[width] duration-1000 ease-[cubic-bezier(0.22,1,0.36,1)] ${s.c}`"
              :style="{ width: mounted ? `${s.s}%` : '0%' }"
            />
          </div>
          <span class="font-mono text-[11px] text-ink3 w-8 text-right">{{ s.s }}%</span>
          <span class="text-[10px] font-bold text-violet bg-violet-light rounded px-1.5 py-0.5">{{ s.g }}</span>
        </div>
      </div>
    </div>
    <div class="rounded-lg bg-blue-light/60 border-l-[3px] border-l-blue px-3 py-2.5">
      <p class="text-[10px] font-semibold text-blue uppercase tracking-[0.1em] mb-0.5">Examiner insight</p>
      <p class="text-[12px] text-ink2 italic leading-snug">"Tighten mechanism arrow labels — 2 marks lost on Q3."</p>
    </div>
  </div>
</template>
