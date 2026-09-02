<script setup lang="ts">
import { Bars, Ring, Sparkline } from "@/components/app/visuals";
import { FileCheck, Sparkles, TrendingUp, Check, X } from "lucide-vue-next";

const marksRows: { label: string; ok?: boolean }[] = [
  { label: "Identifies Newton's 3rd law pair", ok: true },
  { label: "States forces equal in magnitude", ok: true },
  { label: "Opposite direction", ok: true },
  { label: "Acts on different bodies" },
  { label: "Same type (gravitational)" },
];
</script>

<template>
  <section id="how" class="px-6 py-16 bg-bg">
    <div class="max-w-[1100px] mx-auto">
      <div class="text-center mb-10">
        <p class="label-mono mb-3">How it works</p>
        <h2 class="text-[32px] md:text-[44px] text-ink tracking-[-0.02em] leading-[1.05]">
          Three steps to think like an <span class="text-blue">A* student</span>.
        </h2>
        <p class="text-[14px] text-ink3 mt-3 max-w-[560px] mx-auto">
          See exactly what earns marks, how top answers are structured, and where your gap is.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 01 — Diagnose -->
        <div class="relative rounded-[18px] bg-bg-card border border-border overflow-hidden shadow-[0_10px_0_-4px_rgba(14,19,48,0.08),0_18px_30px_-20px_rgba(14,19,48,0.18)] transition-transform duration-300 hover:-translate-y-1 flex flex-col">
          <div class="flex items-center justify-between px-4 py-2 border-b border-border" style="background: var(--blue-light)">
            <span class="font-mono text-[11px] font-semibold text-ink2">01_diagnose</span>
            <FileCheck :size="13" class="text-ink2" />
          </div>
          <div class="p-4 bg-bg-soft/40 flex-1">
            <div class="bg-bg-card border border-border rounded-xl p-4 h-full flex flex-col gap-3">
              <div class="flex items-center justify-between">
                <span class="label-mono">Topic readiness</span>
                <span class="text-[10px] font-mono text-mint bg-mint-light rounded-full px-2 py-0.5">Live</span>
              </div>
              <Bars :rows="[
                { label: 'Mechanics', value: 92, color: 'mint' },
                { label: 'Waves', value: 71, color: 'blue' },
                { label: 'Quantum', value: 44, color: 'pink' },
                { label: 'Fields', value: 58, color: 'gold' },
              ]" />
              <div class="mt-auto bg-bg-soft border-l-[3px] border-l-blue rounded-r-md px-3 py-2">
                <p class="text-[10px] font-semibold text-blue uppercase tracking-[0.07em]">Next gap</p>
                <p class="text-[11.5px] text-ink2 leading-snug">Quantum — direction-pair phrasing</p>
              </div>
            </div>
          </div>
          <div class="px-5 pb-6 pt-3 text-center border-t border-border">
            <h3 class="text-[22px] text-ink mb-1.5">Diagnose.</h3>
            <p class="text-[13px] text-ink3 leading-[1.55]">A 6-minute adaptive test pinpoints what you don't yet know — before you waste hours revising what you already do.</p>
          </div>
        </div>

        <!-- 02 — Mark-scheme feedback -->
        <div class="relative rounded-[18px] bg-bg-card border border-border overflow-hidden shadow-[0_10px_0_-4px_rgba(14,19,48,0.08),0_18px_30px_-20px_rgba(14,19,48,0.18)] transition-transform duration-300 hover:-translate-y-1 flex flex-col">
          <div class="flex items-center justify-between px-4 py-2 border-b border-border" style="background: var(--pink-light)">
            <span class="font-mono text-[11px] font-semibold text-ink2">02_get feedback</span>
            <Sparkles :size="13" class="text-ink2" />
          </div>
          <div class="p-4 bg-bg-soft/40 flex-1">
            <div class="bg-bg-card border border-border rounded-xl p-4 h-full flex flex-col gap-2.5">
              <div class="flex items-center justify-between">
                <span class="label-mono">Mark scheme · Q4(b)</span>
                <span class="font-mono text-[12px] text-ink">5<span class="text-ink3">/7</span></span>
              </div>
              <div v-for="row in marksRows" :key="row.label" class="flex items-center gap-2 text-[12px]">
                <span :class="`w-4 h-4 rounded-full grid place-items-center shrink-0 ${row.ok ? 'bg-mint-light text-mint' : 'bg-pink-light text-pink'}`">
                  <Check v-if="row.ok" :size="10" :stroke-width="3" />
                  <X v-else :size="10" :stroke-width="3" />
                </span>
                <span :class="`${row.ok ? 'text-ink2' : 'text-ink3 line-through'} leading-snug`">{{ row.label }}</span>
              </div>
              <div class="mt-auto bg-bg-soft border-l-[3px] border-l-pink rounded-r-md px-3 py-2">
                <p class="text-[10px] font-semibold text-pink uppercase tracking-[0.07em]">Examiner</p>
                <p class="text-[11.5px] text-ink2 italic leading-snug">"Name the body each force acts on."</p>
              </div>
            </div>
          </div>
          <div class="px-5 pb-6 pt-3 text-center border-t border-border">
            <h3 class="text-[22px] text-ink mb-1.5">Get Feedback.</h3>
            <p class="text-[13px] text-ink3 leading-[1.55]">Write an answer and see it scored against the official mark scheme — every mark point shown, every miss explained.</p>
          </div>
        </div>

        <!-- 03 — Predict -->
        <div class="relative rounded-[18px] bg-bg-card border border-border overflow-hidden shadow-[0_10px_0_-4px_rgba(14,19,48,0.08),0_18px_30px_-20px_rgba(14,19,48,0.18)] transition-transform duration-300 hover:-translate-y-1 flex flex-col">
          <div class="flex items-center justify-between px-4 py-2 border-b border-border" style="background: var(--gold-light)">
            <span class="font-mono text-[11px] font-semibold text-ink2">03_predict</span>
            <TrendingUp :size="13" class="text-ink2" />
          </div>
          <div class="p-4 bg-bg-soft/40 flex-1">
            <div class="bg-bg-card border border-border rounded-xl p-4 h-full flex flex-col gap-3">
              <div class="flex items-center justify-between">
                <span class="label-mono">Grade forecast</span>
                <span class="font-mono text-[10px] text-violet bg-violet-light rounded-full px-2 py-0.5">A*</span>
              </div>
              <div class="flex items-center gap-3">
                <Ring :value="78" :size="88" color="var(--gold)" />
                <div class="flex-1">
                  <p class="text-[11px] text-ink3">Trend · last 8 sessions</p>
                  <Sparkline :points="[52, 58, 56, 61, 65, 70, 74, 78]" color="var(--gold)" :h="36" />
                </div>
              </div>
              <div class="mt-auto bg-bg-soft border-l-[3px] border-l-gold rounded-r-md px-3 py-2">
                <p class="text-[10px] font-semibold text-gold uppercase tracking-[0.07em]">Gap to A*</p>
                <p class="text-[11.5px] text-ink2 leading-snug">+6 marks · focus Quantum & Fields</p>
              </div>
            </div>
          </div>
          <div class="px-5 pb-6 pt-3 text-center border-t border-border">
            <h3 class="text-[22px] text-ink mb-1.5">Predict.</h3>
            <p class="text-[13px] text-ink3 leading-[1.55]">A live grade forecast updates after every session. See exactly what stands between you and an A*.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
