<script setup lang="ts">
import { onMounted, ref } from "vue";
import { BookOpen, CheckCircle2 } from "lucide-vue-next";

const OPTIONS = [
  { l: "A", t: "4.3 × 10⁻¹⁹ J", ok: true },
  { l: "B", t: "6.6 × 10⁻³⁴ J" },
  { l: "C", t: "9.9 × 10²⁰ J" },
];

const mounted = ref(false);
onMounted(() => requestAnimationFrame(() => (mounted.value = true)));
</script>

<template>
  <div class="space-y-3.5">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="grid place-items-center w-7 h-7 rounded-lg bg-violet-light">
          <BookOpen :size="13" class="text-violet" />
        </span>
        <div>
          <p class="text-[12px] font-bold text-ink">Learn Mode</p>
          <p class="text-[10px] text-ink4">Physics · Quantum</p>
        </div>
      </div>
      <span class="text-[10px] font-mono text-ink4">Q 4 / 12</span>
    </div>
    <div class="h-1 rounded-full bg-bg-soft2 overflow-hidden">
      <div class="h-full bg-violet transition-[width] duration-[1100ms] ease-[cubic-bezier(0.22,1,0.36,1)]" :style="{ width: mounted ? '34%' : '0%' }" />
    </div>
    <div class="rounded-xl border border-border bg-bg p-3.5">
      <p class="text-[11px] font-mono text-ink4 mb-1.5">QUESTION</p>
      <p class="text-[13px] text-ink leading-relaxed">
        A photon of frequency <span class="font-mono">6.5 × 10¹⁴ Hz</span> is
        absorbed by an electron. Calculate the energy transferred.
      </p>
    </div>
    <div class="space-y-2">
      <div
        v-for="o in OPTIONS"
        :key="o.l"
        :class="`flex items-center gap-3 rounded-lg border p-2.5 text-[12px] transition ${
          o.ok ? 'border-mint bg-mint-light/50 text-ink' : 'border-border bg-bg-card text-ink2 hover:border-border-mid'
        }`"
      >
        <span :class="`grid place-items-center w-5 h-5 rounded-md font-mono text-[10px] font-bold ${o.ok ? 'bg-mint text-white' : 'bg-bg-soft2 text-ink3'}`">
          {{ o.l }}
        </span>
        <span class="flex-1 font-mono">{{ o.t }}</span>
        <CheckCircle2 v-if="o.ok" :size="13" class="text-mint" />
      </div>
    </div>
    <div class="rounded-lg bg-violet-light/60 border-l-[3px] border-l-violet px-3 py-2">
      <p class="text-[10px] font-semibold text-violet uppercase tracking-[0.1em] mb-0.5">Instant hint</p>
      <p class="text-[11.5px] text-ink2 leading-snug">Use E = hf. Multiply Planck's constant by the frequency.</p>
    </div>
  </div>
</template>
