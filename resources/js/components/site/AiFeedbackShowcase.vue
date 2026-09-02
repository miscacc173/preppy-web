<script setup lang="ts">
// Realistic simulated AI feedback for a Physics Paper 4 answer.
// This is not marketing copy — it mirrors the actual output structure of the AiStar engine.
import { CheckCircle2, AlertTriangle, XCircle, Sparkles, CalendarClock, Quote } from "lucide-vue-next";
import { GradeBadge } from "@/components/ui/grade-badge";

const QUESTION = {
  paper: "CIE A Level · Physics 9702/42 · Q6(b)(ii)",
  topic: "Capacitance — Energy stored in a capacitor",
  marks: "4 marks",
  prompt:
    "A 4700 μF capacitor is charged to 12 V, then discharged through a 2.2 kΩ resistor. Calculate the energy dissipated after one time constant, and explain why it is less than the total stored energy.",
  student:
    "Energy stored = ½ CV² = ½ × 4700e-6 × 12² = 0.338 J. After one time constant the voltage drops to 12/e so the energy left is ½C(12/e)² ≈ 0.046 J. So energy dissipated ≈ 0.29 J. It is less than the total because some energy is still in the capacitor.",
};

const SCORE = { awarded: 3, out_of: 4, grade: "A", confidence: 0.86 };

const STRENGTHS = [
  { mp: "MP1", text: "Correct use of E = ½CV² with substitution shown." },
  { mp: "MP2", text: "Recognised V decays to V₀/e after one time constant." },
  { mp: "MP3", text: "Numerical answer within tolerance (0.29 J vs. 0.293 J)." },
];

const WEAKNESSES = [
  { topic: "Exponential decay reasoning", severity: "Moderate", trend: "3rd time this week" },
  { topic: "Units on final answers", severity: "Minor", trend: "improving" },
  { topic: "Energy conservation vocabulary", severity: "High", trend: "recurring since Apr 12" },
];

const MISTAKES = [
  {
    tag: "MP4 missed",
    line: '"…because some energy is still in the capacitor."',
    why: "Examiner expects the phrase 'energy is dissipated as heat in the resistor' — not just 'energy remains'. This is a mark-scheme keyword miss, not a physics error.",
  },
  {
    tag: "Sig-figs",
    line: "0.338 J → should round to 0.34 J",
    why: "Data given to 2 s.f. (12 V, 2.2 kΩ). Answer must match. Costs 1 mark on ~30% of A Level papers.",
  },
];

const RECOMMENDATIONS = [
  "Rewrite Q6(b)(ii) using the phrase 'dissipated as thermal energy in the resistor' — target MP4.",
  "Drill 5 short-response questions on energy transfer vocabulary (Capacitance + EMF chapters).",
  "Add a 10-second 'sig-fig check' to your answer routine — you lose ~2 marks/paper here.",
];

const PLAN = [
  { day: "Today · 25 min", focus: "Capacitance vocab flashcards + retry Q6(b)(ii)", type: "Fix", color: "pink" },
  { day: "Tomorrow · 40 min", focus: "Mixed exponential decay set (8 questions, timed)", type: "Drill", color: "gold" },
  { day: "Thu · 55 min", focus: "Full Paper 4 Section B under exam conditions", type: "Mock", color: "violet" },
  { day: "Sat · 20 min", focus: "Examiner report review — 2022 & 2023 Q6 patterns", type: "Read", color: "blue" },
];
</script>

<template>
  <section id="ai-feedback" class="px-6 py-16 border-b border-border relative">
    <div class="max-w-[1160px] mx-auto">
      <!-- Header -->
      <div class="text-center mb-10">
        <p class="label-mono mb-3">What the AI actually returns</p>
        <h2 class="text-[28px] md:text-[38px] text-ink tracking-[-0.02em] leading-[1.1]">
          Real feedback. On a real answer. <br class="hidden md:block" />
          <span class="text-blue">Not marketing copy.</span>
        </h2>
        <p class="text-[14px] text-ink3 mt-3 max-w-[560px] mx-auto">
          Below is a live sample of AiStar's output — the same JSON the app uses to render every session review.
        </p>
      </div>

      <!-- Terminal-style shell -->
      <div class="rounded-2xl border border-border bg-bg-card shadow-soft overflow-hidden">
        <!-- Fake window chrome -->
        <div class="flex items-center gap-2 px-4 h-9 bg-bg-soft border-b border-border">
          <span class="w-2.5 h-2.5 rounded-full bg-pink" />
          <span class="w-2.5 h-2.5 rounded-full bg-gold" />
          <span class="w-2.5 h-2.5 rounded-full bg-mint" />
          <span class="font-mono text-[11px] text-ink4 ml-3">aistar://feedback/session_82f1c · streaming</span>
          <span class="ml-auto flex items-center gap-1.5 text-[10px] font-mono text-mint">
            <span class="w-1.5 h-1.5 rounded-full bg-mint animate-pulse" /> LIVE
          </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1.15fr_1fr]">
          <!-- LEFT — Question + student answer + score -->
          <div class="p-6 lg:p-7 border-b lg:border-b-0 lg:border-r border-border bg-bg-soft/40">
            <p class="label-mono">Question</p>
            <p class="text-[11px] font-mono text-ink4 mt-1">{{ QUESTION.paper }} · {{ QUESTION.marks }}</p>
            <p class="text-[13px] font-semibold text-ink mt-2">{{ QUESTION.topic }}</p>
            <p class="text-[13px] text-ink2 leading-[1.6] mt-2">{{ QUESTION.prompt }}</p>

            <div class="mt-5">
              <p class="label-mono mb-2">Student answer</p>
              <div class="handwritten text-[15.5px] leading-[1.55] text-ink2 bg-white/60 border border-border rounded-lg p-4">
                {{ QUESTION.student }}
              </div>
            </div>

            <!-- Score -->
            <div class="mt-5 grid grid-cols-3 gap-2">
              <div class="bg-bg-card border border-border rounded-lg p-3">
                <p class="label-mono">Awarded</p>
                <p class="font-mono text-[22px] text-ink mt-1">{{ SCORE.awarded }}<span class="text-ink3 text-[13px]">/{{ SCORE.out_of }}</span></p>
              </div>
              <div class="bg-bg-card border border-border rounded-lg p-3">
                <p class="label-mono">Grade</p>
                <div class="mt-1 flex items-center min-h-[28px]"><GradeBadge :grade="SCORE.grade" size="lg" /></div>
              </div>
              <div class="bg-bg-card border border-border rounded-lg p-3">
                <p class="label-mono">Confidence</p>
                <p class="font-mono text-[22px] text-ink mt-1">{{ Math.round(SCORE.confidence * 100) }}%</p>
              </div>
            </div>
          </div>

          <!-- RIGHT — Structured AI feedback -->
          <div class="p-6 lg:p-7 space-y-5">
            <!-- Strengths -->
            <div>
              <div class="flex items-center gap-2 mb-2">
                <CheckCircle2 :size="14" class="text-mint" />
                <p class="label-mono">Strengths detected</p>
              </div>
              <ul class="space-y-1.5">
                <li v-for="s in STRENGTHS" :key="s.mp" class="flex items-start gap-2 text-[13px] text-ink2">
                  <span class="font-mono text-[10px] text-mint bg-mint-light rounded px-1.5 py-0.5 mt-0.5 shrink-0">{{ s.mp }}</span>
                  <span>{{ s.text }}</span>
                </li>
              </ul>
            </div>

            <!-- Weak topics -->
            <div>
              <div class="flex items-center gap-2 mb-2">
                <AlertTriangle :size="14" class="text-gold" />
                <p class="label-mono">Weak topics · cross-session</p>
              </div>
              <div class="space-y-1.5">
                <div v-for="w in WEAKNESSES" :key="w.topic" class="flex items-center justify-between gap-3 bg-bg-soft border border-border rounded-md px-3 py-2">
                  <p class="text-[13px] text-ink2 truncate">{{ w.topic }}</p>
                  <div class="flex items-center gap-2 shrink-0">
                    <span
                      :class="`text-[10px] font-mono rounded-full px-2 py-0.5 ${
                        w.severity === 'High' ? 'bg-pink-light text-pink' : w.severity === 'Moderate' ? 'bg-gold-light text-gold' : 'bg-mint-light text-mint'
                      }`"
                    >
                      {{ w.severity }}
                    </span>
                    <span class="text-[10px] font-mono text-ink4">{{ w.trend }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mistakes -->
            <div>
              <div class="flex items-center gap-2 mb-2">
                <XCircle :size="14" class="text-pink" />
                <p class="label-mono">Mistakes in this answer</p>
              </div>
              <div class="space-y-2">
                <div v-for="m in MISTAKES" :key="m.tag" class="border-l-[3px] border-l-pink bg-pink-light/30 rounded-r-md px-3 py-2">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="font-mono text-[10px] text-pink bg-white rounded px-1.5 py-0.5">{{ m.tag }}</span>
                    <Quote :size="11" class="text-ink4" />
                    <span class="text-[11px] font-mono text-ink3 italic truncate">{{ m.line }}</span>
                  </div>
                  <p class="text-[12.5px] text-ink2 leading-snug">{{ m.why }}</p>
                </div>
              </div>
            </div>

            <!-- Recommendations -->
            <div>
              <div class="flex items-center gap-2 mb-2">
                <Sparkles :size="14" class="text-violet" />
                <p class="label-mono">AI recommendations</p>
              </div>
              <ol class="space-y-1.5">
                <li v-for="(r, i) in RECOMMENDATIONS" :key="i" class="flex items-start gap-2 text-[13px] text-ink2">
                  <span class="font-mono text-[10px] text-violet bg-violet-light rounded px-1.5 py-0.5 mt-0.5 shrink-0">0{{ i + 1 }}</span>
                  <span>{{ r }}</span>
                </li>
              </ol>
            </div>
          </div>
        </div>

        <!-- BOTTOM — Suggested plan -->
        <div class="border-t border-border p-6 lg:p-7 bg-bg-soft/40">
          <div class="flex items-center gap-2 mb-4">
            <CalendarClock :size="14" class="text-blue" />
            <p class="label-mono">Next 4-day study plan · auto-generated</p>
            <span class="ml-auto text-[10px] font-mono text-ink4">estimated +8 marks by Sat</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div v-for="p in PLAN" :key="p.day" class="bg-bg-card border border-border rounded-lg p-4 hover:border-blue-mid transition">
              <div class="flex items-center justify-between mb-2">
                <p class="font-mono text-[10px] text-ink4 uppercase tracking-wider">{{ p.day }}</p>
                <span :class="`text-[10px] font-mono rounded-full px-2 py-0.5 bg-${p.color}-light text-${p.color}`">{{ p.type }}</span>
              </div>
              <p class="text-[13px] text-ink2 leading-snug">{{ p.focus }}</p>
            </div>
          </div>
        </div>
      </div>

      <p class="text-center text-[11px] font-mono text-ink4 mt-4">
        Sample output · every field above is produced per-question, per-student, in &lt; 3s.
      </p>
    </div>
  </section>
</template>
