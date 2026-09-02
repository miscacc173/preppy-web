<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { ResumeSessionsList } from "@/components/app/resume-session";
import { useInProgressSessions } from "@/lib/learnSessionStore";
import { useSessions } from "@/lib/sessionStore";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiAccount } from "@/data/models/platform";
import { ArrowRight, BookOpen, PlayCircle, Timer, CheckCircle2 } from "lucide-vue-next";

setPageMeta({ title: "Dashboard — AiStar Prep", description: "Your student learning dashboard." });

const account = ref<UiAccount | null>(null);
const loading = ref(true);
const error = ref("");
const sessions = useSessions();
const inProgress = useInProgressSessions();

const completedSessions = computed(() => sessions.value.length);
const questionsAnswered = computed(() => sessions.value.reduce((sum, session) => sum + session.questions.length, 0));
const averageAccuracy = computed(() => {
  if (!sessions.value.length) return null;
  return Math.round(sessions.value.reduce((sum, session) => sum + session.accuracy, 0) / sessions.value.length);
});
const recentSessions = computed(() => sessions.value.slice(0, 5));
const courseLabel = computed(() => account.value?.course?.name || account.value?.course?.level || "Course not selected");

onMounted(async () => {
  try {
    account.value = (await platformRepository.account()).account;
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Dashboard data could not be loaded.";
  } finally {
    loading.value = false;
  }
});

const quickActions = [
  { icon: BookOpen, title: "Start learning session", description: "Practice a selected subject with guided feedback.", to: "/learn", tone: "violet" },
  { icon: PlayCircle, title: "Continue a session", description: "Resume any unfinished Learn or Exam session.", to: "/learn", tone: "blue" },
  { icon: Timer, title: "Start mock exam", description: "Begin a timed exam session when you are ready.", to: "/exam", tone: "gold" },
];
</script>

<template>
  <AppShell title="Dashboard" :back="{ to: '/', label: 'Back to home' }">
    <div v-if="loading" class="mb-5 rounded-xl border border-border bg-bg-card p-5 text-[13px] text-ink3">Loading dashboard…</div>
    <div v-if="error" class="mb-5 rounded-xl border border-pink/30 bg-pink-light p-4 text-[13px] text-pink">{{ error }}</div>

    <section class="bg-bg-card border border-border rounded-2xl p-6 lg:p-7 reveal-up">
      <p class="label-mono mb-2">Student dashboard</p>
      <h2 class="text-[28px] md:text-[34px] font-bold text-ink tracking-[-0.025em] leading-tight">
        Welcome{{ account?.name ? `, ${account.name}` : "" }}.
      </h2>
      <p class="text-[14px] text-ink3 mt-2 max-w-[720px]">
        {{ courseLabel }} · {{ account?.examBoard?.name || "Exam board not selected" }}
      </p>
      <div v-if="account?.subjects.length" class="flex flex-wrap gap-2 mt-4">
        <span v-for="subject in account.subjects" :key="subject.id" class="text-[12px] font-medium rounded-full px-3 py-1.5 bg-blue-light text-blue border border-blue-light">
          {{ subject.name }}
        </span>
      </div>
      <p v-else-if="!loading" class="text-[13px] text-ink3 mt-4">Complete Student Setup to add your subjects.</p>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
      <div class="bg-bg-card border border-border rounded-xl p-5">
        <p class="label-mono">Completed sessions</p>
        <p class="font-mono text-[30px] font-medium text-ink mt-2">{{ completedSessions }}</p>
        <p class="text-[12px] text-ink3 mt-1">Learn and Exam sessions completed on this device.</p>
      </div>
      <div class="bg-bg-card border border-border rounded-xl p-5">
        <p class="label-mono">Questions answered</p>
        <p class="font-mono text-[30px] font-medium text-ink mt-2">{{ questionsAnswered }}</p>
        <p class="text-[12px] text-ink3 mt-1">Based on your completed sessions.</p>
      </div>
      <div class="bg-bg-card border border-border rounded-xl p-5">
        <p class="label-mono">Average accuracy</p>
        <p class="font-mono text-[30px] font-medium text-ink mt-2">{{ averageAccuracy === null ? "—" : `${averageAccuracy}%` }}</p>
        <p class="text-[12px] text-ink3 mt-1">{{ averageAccuracy === null ? "Complete a session to establish an average." : "Calculated from completed sessions." }}</p>
      </div>
    </section>

    <section v-if="inProgress.length > 0" class="mt-5">
      <ResumeSessionsList />
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-5">
      <RouterLink v-for="action in quickActions" :key="action.title" :to="action.to" class="group bg-bg-card border border-border rounded-xl p-5 hover:border-blue-mid hover:shadow-soft transition">
        <div :class="`w-10 h-10 rounded-[10px] grid place-items-center mb-4 bg-${action.tone}-light`">
          <component :is="action.icon" :size="18" :class="`text-${action.tone}`" />
        </div>
        <p class="text-[15px] font-semibold text-ink mb-1">{{ action.title }}</p>
        <p class="text-[13px] text-ink3">{{ action.description }}</p>
        <div class="flex items-center gap-1.5 text-[12px] text-blue mt-4 font-medium group-hover:gap-2.5 transition-all">
          Open <ArrowRight :size="13" />
        </div>
      </RouterLink>
    </section>

    <section class="mt-7">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-[15px] font-semibold text-ink">Your subjects</h3>
        <RouterLink to="/student" class="text-[12px] text-blue font-medium">Manage setup</RouterLink>
      </div>
      <div v-if="account?.subjects.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <RouterLink v-for="subject in account.subjects" :key="subject.id" :to="{ path: '/learn/session', query: { subject: subject.name, source: 'dashboard' } }" class="bg-bg-card border border-border rounded-xl p-5 hover:border-violet hover:shadow-soft transition block">
          <p class="label-mono mb-2">Selected subject</p>
          <p class="text-[16px] font-semibold text-ink">{{ subject.name }}</p>
          <p class="text-[12px] text-ink3 mt-2">Performance insights will appear after you complete relevant learning or exam activity.</p>
          <span class="inline-flex items-center gap-1.5 text-[12px] text-violet font-medium mt-4">Practice subject <ArrowRight :size="13" /></span>
        </RouterLink>
      </div>
      <div v-else class="bg-bg-card border border-border rounded-xl p-6 text-center">
        <p class="text-[14px] font-semibold text-ink">No subjects selected yet</p>
        <p class="text-[13px] text-ink3 mt-1">Complete Student Setup before starting subject-based learning.</p>
        <RouterLink to="/student" class="inline-flex items-center gap-2 bg-violet text-white text-[13px] font-semibold px-4 py-2.5 rounded-md mt-4">Complete setup <ArrowRight :size="13" /></RouterLink>
      </div>
    </section>

    <section class="mt-7">
      <h3 class="text-[15px] font-semibold text-ink mb-4">Recent activity</h3>
      <div v-if="recentSessions.length" class="bg-bg-card border border-border rounded-xl overflow-hidden">
        <div v-for="session in recentSessions" :key="session.id" class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-border last:border-0">
          <CheckCircle2 :size="16" class="text-mint shrink-0" />
          <div class="min-w-0 flex-1">
            <p class="text-[13px] font-medium text-ink truncate">{{ session.subject }} · {{ session.topic }}</p>
            <p class="text-[11px] text-ink4">{{ session.type }} · {{ session.dateLabel }}</p>
          </div>
          <span class="font-mono text-[12px] text-ink">{{ session.accuracy }}%</span>
          <span class="font-mono text-[11px] text-violet bg-violet-light rounded-full px-2 py-0.5">{{ session.grade }}</span>
        </div>
      </div>
      <div v-else class="bg-bg-card border border-border rounded-xl p-6 text-center">
        <p class="text-[14px] font-semibold text-ink">No completed sessions yet</p>
        <p class="text-[13px] text-ink3 mt-1">Your completed Learn and Exam sessions will appear here.</p>
      </div>
    </section>
  </AppShell>
</template>
