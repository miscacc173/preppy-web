<script setup lang="ts">
/**
 * Landing "Inside AiStar" showcase — a compact, non-interactive replica of the
 * real dashboard framed as a product preview with subtle callouts pointing to
 * the features that matter most.
 */
import {
  ArrowRight, LayoutDashboard, BookOpen, Timer, BarChart3, Gauge,
  Settings, Search, Bell, Flame, Trophy, Target, ChevronRight,
} from "lucide-vue-next";
import { GradeBadge } from "@/components/ui/grade-badge";
import { SUBJECTS } from ".";
import Callout from "./Callout.vue";
import MiniSpark from "./MiniSpark.vue";

const sidebarItems = [
  { i: LayoutDashboard, l: "Dashboard", active: true },
  { i: BookOpen, l: "Learn Mode" },
  { i: Timer, l: "Exam Mode" },
  { i: BarChart3, l: "Results" },
  { i: Gauge, l: "Readiness" },
  { i: Settings, l: "Settings" },
];

const heroStats = [
  { icon: Flame, label: "Streak", value: "14d", tone: "gold" as const },
  { icon: Trophy, label: "Predicted", value: "A", tone: "violet" as const },
  { icon: Target, label: "Readiness", value: "74%", tone: "mint" as const },
];

const mobileCallouts = [
  { l: "01", t: "Live readiness score", d: "Predicts your grade from every session." },
  { l: "02", t: "Per-subject breakdown", d: "Strengths, weaknesses, and pacing." },
  { l: "03", t: "Examiner insight", d: "Feedback pulled from real mark schemes." },
  { l: "04", t: "Next up, always", d: "AI queues what moves your grade fastest." },
];
</script>

<template>
  <section class="px-6 py-16 border-b border-border bg-bg-soft/40 relative overflow-hidden">
    <div class="max-w-[1180px] mx-auto">
      <!-- Section heading -->
      <div class="flex items-end justify-between flex-wrap gap-3 mb-8">
        <div class="max-w-[620px]">
          <p class="label-mono mb-3">Inside AiStar</p>
          <h2 class="text-[28px] md:text-[36px] text-ink tracking-[-0.02em] leading-tight">
            Your exam-readiness command centre. <span class="text-ink3">One glance. Every subject.</span>
          </h2>
        </div>
        <RouterLink to="/dashboard" class="text-[13px] text-blue font-semibold inline-flex items-center gap-1 hover:gap-2 transition-all">
          Open dashboard <ArrowRight :size="13" />
        </RouterLink>
      </div>

      <!-- Product frame + callouts -->
      <div class="relative">
        <!-- Ambient blue glow -->
        <div
          aria-hidden
          class="absolute -inset-6 -z-10 rounded-[32px] blur-3xl opacity-40"
          style="background: radial-gradient(60% 50% at 50% 40%, color-mix(in oklab, var(--color-blue, #3b82f6) 22%, transparent), transparent 70%)"
        />

        <!-- Browser-style chrome -->
        <div class="rounded-[20px] border border-border bg-bg-card shadow-lift overflow-hidden">
          <!-- Titlebar -->
          <div class="flex items-center gap-3 px-4 h-10 border-b border-border bg-bg-soft/70">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-[#ff5f57]" />
              <span class="w-2.5 h-2.5 rounded-full bg-[#febc2e]" />
              <span class="w-2.5 h-2.5 rounded-full bg-[#28c840]" />
            </div>
            <div class="mx-auto flex items-center gap-2 text-[11px] font-mono text-ink4 bg-bg border border-border rounded-md px-3 py-1">
              <span class="w-1.5 h-1.5 rounded-full bg-mint" /> aistarprep.com/dashboard
            </div>
          </div>

          <!-- App layout -->
          <div class="grid grid-cols-[160px_1fr] min-h-[440px]">
            <!-- Sidebar -->
            <aside class="hidden md:flex flex-col gap-1 p-3 border-r border-border bg-bg-card">
              <div class="px-2 py-2 mb-2">
                <span class="font-display text-[15px] tracking-[-0.01em] text-ink">AiStar</span>
              </div>
              <div
                v-for="item in sidebarItems"
                :key="item.l"
                :class="`flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[12px] font-medium ${item.active ? 'bg-blue-light text-blue' : 'text-ink3'}`"
              >
                <component :is="item.i" :size="13" :stroke-width="2" /> {{ item.l }}
              </div>
            </aside>

            <!-- Main pane -->
            <div class="flex flex-col">
              <!-- Top bar -->
              <div class="flex items-center gap-3 px-5 h-12 border-b border-border">
                <h3 class="text-[14px] font-bold text-ink tracking-[-0.01em] flex-1">Dashboard</h3>
                <div class="hidden sm:flex items-center gap-2 bg-bg-soft border border-border rounded-md px-2.5 py-1 w-[180px]">
                  <Search :size="11" class="text-ink4" />
                  <span class="text-[11px] text-ink4">Search topics…</span>
                </div>
                <Bell :size="14" class="text-ink3" />
                <div class="w-7 h-7 rounded-full bg-violet-light grid place-items-center text-[10px] font-bold text-violet">ZH</div>
              </div>

              <!-- Content -->
              <div class="p-5 grid grid-cols-1 lg:grid-cols-[1fr_220px] gap-5 flex-1">
                <!-- Left column -->
                <div class="space-y-4 min-w-0">
                  <!-- Hero stat row -->
                  <div class="grid grid-cols-3 gap-3">
                    <div v-for="s in heroStats" :key="s.label" class="rounded-lg border border-border bg-bg p-3">
                      <div class="flex items-center gap-1.5 mb-1.5">
                        <component :is="s.icon" :size="12" :class="`text-${s.tone}`" />
                        <span class="text-[10px] uppercase tracking-[0.08em] text-ink4 font-semibold">{{ s.label }}</span>
                      </div>
                      <div class="font-mono text-[22px] tracking-[-0.03em] text-ink leading-none">{{ s.value }}</div>
                    </div>
                  </div>

                  <!-- Subject readiness cards -->
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-ink4">Subjects</p>
                      <span class="text-[10px] text-ink4">3 papers · updated live</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                      <div v-for="s in SUBJECTS" :key="s.name" class="rounded-lg border border-border bg-bg p-3">
                        <div class="flex items-center justify-between mb-1.5">
                          <span class="text-[12px] font-display text-ink">{{ s.name }}</span>
                          <span v-if="s.live" class="w-1.5 h-1.5 rounded-full bg-mint animate-pulse" />
                        </div>
                        <div class="flex items-baseline gap-1">
                          <span class="font-mono text-[20px] text-ink leading-none tracking-[-0.02em]">{{ s.score }}</span>
                          <span class="font-mono text-[10px] text-ink3">%</span>
                          <GradeBadge :grade="s.grade" size="sm" class="ml-auto" />
                        </div>
                        <div class="mt-2 h-1 rounded-full bg-bg-soft2 overflow-hidden">
                          <div :class="`h-full rounded-full ${s.color === 'mint' ? 'bg-mint' : 'bg-gold'}`" :style="{ width: `${s.score}%` }" />
                        </div>
                        <p class="mt-1.5 text-[10px] text-ink4">Trend {{ s.trend }}%</p>
                      </div>
                    </div>
                  </div>

                  <!-- Examiner insight -->
                  <div class="rounded-lg bg-bg-soft border-l-[3px] border-l-blue px-3 py-2.5">
                    <p class="text-[10px] font-semibold text-blue uppercase tracking-[0.08em] mb-0.5">Examiner insight</p>
                    <p class="text-[12px] text-ink2 italic leading-snug">"Tighten mechanism arrow labels — 2 marks lost on Q3."</p>
                  </div>
                </div>

                <!-- Right rail -->
                <div class="hidden lg:flex flex-col gap-3">
                  <div class="rounded-lg border border-border bg-bg p-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-ink4 mb-2">Next up</p>
                    <div class="flex items-center gap-2 py-1.5">
                      <div class="w-7 h-7 rounded-md bg-blue-light grid place-items-center">
                        <BookOpen :size="12" class="text-blue" />
                      </div>
                      <div class="min-w-0">
                        <p class="text-[11.5px] text-ink font-medium truncate">Quantum recap</p>
                        <p class="text-[10px] text-ink4">3 min · Physics</p>
                      </div>
                      <ChevronRight :size="12" class="ml-auto text-ink4" />
                    </div>
                    <div class="flex items-center gap-2 py-1.5 border-t border-border">
                      <div class="w-7 h-7 rounded-md bg-violet-light grid place-items-center">
                        <Timer :size="12" class="text-violet" />
                      </div>
                      <div class="min-w-0">
                        <p class="text-[11.5px] text-ink font-medium truncate">Organic mock</p>
                        <p class="text-[10px] text-ink4">12 min · Chem</p>
                      </div>
                      <ChevronRight :size="12" class="ml-auto text-ink4" />
                    </div>
                  </div>

                  <!-- Mini trend -->
                  <div class="rounded-lg border border-border bg-bg p-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-ink4 mb-2">12-week trend</p>
                    <MiniSpark />
                    <p class="mt-1 font-mono text-[11px] text-mint">+18% vs last month</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Callouts -->
        <Callout class="hidden lg:flex left-[-14px] top-[104px] -translate-x-full" side="right" label="01" title="Live readiness score" desc="Predicts your grade from every session." />
        <Callout class="hidden lg:flex left-[calc(160px+180px)] top-[64px]" side="up" label="02" title="Per-subject breakdown" desc="Strengths, weaknesses, and pacing — all here." />
        <Callout class="hidden lg:flex right-[-14px] top-[220px] translate-x-full" side="left" label="03" title="Examiner insight" desc="Feedback pulled from real mark schemes." />
        <Callout class="hidden lg:flex right-[-14px] bottom-[80px] translate-x-full" side="left" label="04" title="Next up, always" desc="AI queues what will move your grade fastest." />
      </div>

      <!-- Mobile callout list -->
      <div class="lg:hidden grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6">
        <div v-for="c in mobileCallouts" :key="c.l" class="notepad p-3 flex gap-3">
          <span class="font-mono text-[11px] text-blue font-semibold shrink-0">{{ c.l }}</span>
          <div>
            <p class="text-[13px] font-display text-ink">{{ c.t }}</p>
            <p class="text-[11.5px] text-ink3 mt-0.5">{{ c.d }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
