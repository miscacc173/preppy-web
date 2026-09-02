<script setup lang="ts">
import { Brain, CheckCircle2, Sparkles } from "lucide-vue-next";

const FEEDBACK = [
  { t: "Correct mention of collision frequency", ok: true, c: "mint" as const },
  { t: "Correct link to activation energy", ok: true, c: "mint" as const },
  { t: "Missing: Maxwell–Boltzmann distribution reference", ok: false, c: "gold" as const },
];
</script>

<template>
  <div class="space-y-3.5">
    <div class="flex items-center gap-2">
      <span class="grid place-items-center w-7 h-7 rounded-lg bg-blue-light">
        <Brain :size="13" class="text-blue" />
      </span>
      <div class="flex-1">
        <p class="text-[12px] font-bold text-ink">AI Marker</p>
        <p class="text-[10px] text-ink4">Marking against AQA scheme…</p>
      </div>
      <span class="font-mono text-[11px] font-bold text-mint">7 / 8</span>
    </div>

    <div class="rounded-xl border border-border bg-bg p-3">
      <p class="text-[10px] font-mono text-ink4 mb-1.5">YOUR ANSWER</p>
      <p class="text-[12px] text-ink2 leading-relaxed italic">
        "The rate increases because more collisions occur per second, giving more
        successful collisions above activation energy."
      </p>
    </div>

    <div class="space-y-2">
      <div v-for="f in FEEDBACK" :key="f.t" class="flex items-start gap-2.5 rounded-lg border border-border bg-bg-card p-2.5">
        <span :class="`shrink-0 mt-0.5 grid place-items-center w-4 h-4 rounded-full ${f.c === 'mint' ? 'bg-mint' : 'bg-gold'}`">
          <CheckCircle2 v-if="f.ok" :size="10" class="text-white" :stroke-width="3" />
          <span v-else class="w-1 h-1 rounded-full bg-white" />
        </span>
        <span class="text-[11.5px] text-ink2 leading-snug">{{ f.t }}</span>
      </div>
    </div>

    <div class="rounded-lg bg-blue-light/60 border-l-[3px] border-l-blue px-3 py-2">
      <p class="text-[10px] font-semibold text-blue uppercase tracking-[0.1em] mb-0.5 flex items-center gap-1.5">
        <Sparkles :size="10" /> Examiner tip
      </p>
      <p class="text-[11.5px] text-ink2 leading-snug">
        Reference the shifted energy distribution curve to secure the final mark.
      </p>
    </div>
  </div>
</template>
