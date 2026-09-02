<script setup lang="ts">
import { computed } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { useSessions } from "@/lib/sessionStore";
import { BarChart3, BookOpen, Timer, ArrowRight } from "lucide-vue-next";

setPageMeta({ title: "Readiness report — AiStar Prep" });

const sessions = useSessions();
const learnCount = computed(() => sessions.value.filter((session) => session.type === "Learn").length);
const examCount = computed(() => sessions.value.filter((session) => session.type === "Exam").length);
const questionsAnswered = computed(() => sessions.value.reduce((sum, session) => sum + session.questions.length, 0));
</script>

<template>
  <AppShell title="Readiness report" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <section class="bg-bg-card border border-border rounded-2xl p-7 lg:p-9 reveal-up">
      <div class="max-w-[760px]">
        <div class="w-12 h-12 rounded-xl bg-violet-light grid place-items-center mb-5"><BarChart3 :size="22" class="text-violet" /></div>
        <p class="label-mono mb-2">Readiness analytics</p>
        <h2 class="text-[26px] lg:text-[34px] font-bold text-ink tracking-[-0.025em] leading-tight">Your readiness report is not available yet.</h2>
        <p class="text-[14px] text-ink2 mt-3 leading-relaxed">
          AiStar Prep will only show predicted grades, skill readiness, grade gaps, and recommendations when those metrics are backed by the approved assessment and analytics workflow. We are not presenting placeholder percentages as student performance.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-7">
        <div class="bg-bg-soft border border-border rounded-xl p-5">
          <BookOpen :size="17" class="text-violet mb-3" />
          <p class="label-mono">Learn sessions</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-1">{{ learnCount }}</p>
        </div>
        <div class="bg-bg-soft border border-border rounded-xl p-5">
          <Timer :size="17" class="text-gold mb-3" />
          <p class="label-mono">Exam sessions</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-1">{{ examCount }}</p>
        </div>
        <div class="bg-bg-soft border border-border rounded-xl p-5">
          <BarChart3 :size="17" class="text-blue mb-3" />
          <p class="label-mono">Questions recorded</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-1">{{ questionsAnswered }}</p>
        </div>
      </div>

      <div class="mt-7 rounded-xl border border-blue-light bg-blue-light/40 p-5">
        <p class="text-[13px] font-semibold text-ink">What you can do now</p>
        <p class="text-[13px] text-ink3 mt-1">Complete Learn and Exam sessions while the production readiness model is being connected.</p>
        <div class="flex flex-wrap gap-3 mt-4">
          <RouterLink to="/learn" class="inline-flex items-center gap-2 bg-violet text-white text-[13px] font-semibold px-4 py-2.5 rounded-md">Open Learn Mode <ArrowRight :size="13" /></RouterLink>
          <RouterLink to="/exam" class="inline-flex items-center gap-2 bg-gold text-white text-[13px] font-semibold px-4 py-2.5 rounded-md">Open Exam Mode <ArrowRight :size="13" /></RouterLink>
        </div>
      </div>
    </section>
  </AppShell>
</template>
