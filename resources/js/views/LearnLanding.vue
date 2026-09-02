<script setup lang="ts">
import { computed } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { ArrowRight, PlayCircle, BookOpen, Clock } from "lucide-vue-next";
import { ResumeSessionsList } from "@/components/app/resume-session";
import { useSessions } from "@/lib/sessionStore";

setPageMeta({
  title: "Learn Mode — AiStar Prep",
  description: "Start or resume a learning session and review your completed practice.",
});

const sessions = useSessions();
const learnSessions = computed(() => sessions.value.filter((session) => session.type === "Learn"));
const recent = computed(() => learnSessions.value.slice(0, 5));
const questions = computed(() => learnSessions.value.reduce((sum, session) => sum + session.questions.length, 0));
const minutes = computed(() => Math.round(learnSessions.value.reduce((sum, session) => sum + session.timeSec, 0) / 60));
const averageAccuracy = computed(() => {
  if (!learnSessions.value.length) return null;
  return Math.round(learnSessions.value.reduce((sum, session) => sum + session.accuracy, 0) / learnSessions.value.length);
});
</script>

<template>
  <AppShell title="Learn Mode" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="space-y-6">
      <ResumeSessionsList compact />

      <section class="bg-gradient-to-br from-violet-light to-blue-light/40 border border-border rounded-2xl p-6 lg:p-8 reveal-up">
        <p class="label-mono mb-2">Learn Mode</p>
        <h2 class="text-[24px] lg:text-[32px] font-bold text-ink tracking-[-0.025em] leading-tight">Practice one topic at a time.</h2>
        <p class="text-[14px] lg:text-[16px] text-ink2 mt-2 max-w-[620px]">
          Start a learning session, answer questions, and review the feedback produced by the current practice workflow.
        </p>
        <div class="flex flex-wrap gap-3 mt-6">
          <RouterLink to="/learn/session" class="inline-flex items-center gap-2 bg-violet text-white text-[15px] font-semibold px-6 h-12 rounded-lg hover:opacity-90">
            <PlayCircle :size="18" /> Start learning session
          </RouterLink>
        </div>
      </section>

      <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-bg-card border border-border rounded-2xl p-5">
          <p class="label-mono">Completed sessions</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-2">{{ learnSessions.length }}</p>
        </div>
        <div class="bg-bg-card border border-border rounded-2xl p-5">
          <p class="label-mono">Questions answered</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-2">{{ questions }}</p>
        </div>
        <div class="bg-bg-card border border-border rounded-2xl p-5">
          <p class="label-mono">Average accuracy</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-2">{{ averageAccuracy === null ? "—" : `${averageAccuracy}%` }}</p>
          <p class="text-[11px] text-ink4 mt-1">{{ minutes }} minutes practiced</p>
        </div>
      </section>

      <section>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-[16px] font-semibold text-ink flex items-center gap-2"><Clock :size="16" class="text-ink3" /> Recent learning sessions</h3>
          <RouterLink to="/results" class="text-[13px] text-blue font-medium">View results</RouterLink>
        </div>
        <div v-if="recent.length" class="bg-bg-card border border-border rounded-2xl divide-y divide-border overflow-hidden">
          <RouterLink v-for="session in recent" :key="session.id" to="/results" class="flex items-center gap-4 p-4 hover:bg-bg-soft transition">
            <div class="w-10 h-10 rounded-md bg-violet-light grid place-items-center shrink-0"><BookOpen :size="16" class="text-violet" /></div>
            <div class="min-w-0 flex-1">
              <p class="text-[14px] font-medium text-ink truncate">{{ session.subject }} · {{ session.topic }}</p>
              <p class="text-[12px] text-ink3">{{ session.dateLabel }}</p>
            </div>
            <span class="font-mono text-[13px] text-ink font-semibold shrink-0">{{ session.accuracy }}%</span>
            <ArrowRight :size="14" class="text-ink4 shrink-0" />
          </RouterLink>
        </div>
        <div v-else class="bg-bg-card border border-border rounded-2xl p-7 text-center">
          <p class="text-[15px] font-semibold text-ink">No learning history yet</p>
          <p class="text-[13px] text-ink3 mt-1">Complete your first learning session to start building activity history.</p>
          <RouterLink to="/learn/session" class="inline-flex items-center gap-2 bg-violet text-white text-[13px] font-semibold px-4 py-2.5 rounded-md mt-4">Start first session <ArrowRight :size="13" /></RouterLink>
        </div>
      </section>
    </div>
  </AppShell>
</template>
