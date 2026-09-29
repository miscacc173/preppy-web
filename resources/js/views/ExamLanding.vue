<script setup lang="ts">
import { computed } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { ArrowRight, Timer as TimerIcon, History, Trophy } from "lucide-vue-next";
import { useSessions } from "@/lib/sessionStore";

setPageMeta({
  title: "Exam Mode — Preppy",
  description: "Start timed mock exams and review completed exam sessions.",
});

const sessions = useSessions();
const exams = computed(() => sessions.value.filter((session) => session.type === "Exam"));
const latest = computed(() => exams.value[0] ?? null);
const recent = computed(() => exams.value.slice(0, 5));
const averageAccuracy = computed(() => {
  if (!exams.value.length) return null;
  return Math.round(exams.value.reduce((sum, session) => sum + session.accuracy, 0) / exams.value.length);
});
</script>

<template>
  <AppShell title="Exam Mode" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="space-y-6">
      <section class="bg-gradient-to-br from-gold-light to-bg-card border border-border rounded-2xl p-6 lg:p-8 reveal-up">
        <p class="label-mono mb-2">Mock exams</p>
        <h2 class="text-[28px] lg:text-[36px] font-bold text-ink tracking-[-0.025em]">Practice under exam conditions.</h2>
        <p class="text-[14px] text-ink2 mt-2 max-w-[620px]">Configure and complete a timed mock exam using the current Exam Mode workflow.</p>
        <div class="flex flex-wrap gap-3 mt-6">
          <RouterLink to="/exam/session" class="inline-flex items-center gap-2 bg-gold text-white text-[15px] font-semibold px-6 h-12 rounded-lg hover:opacity-90">
            <TimerIcon :size="18" /> Start mock exam
          </RouterLink>
        </div>
      </section>

      <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-bg-card border border-border rounded-2xl p-5">
          <p class="label-mono">Mocks completed</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-2">{{ exams.length }}</p>
        </div>
        <div class="bg-bg-card border border-border rounded-2xl p-5">
          <p class="label-mono">Latest result</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-2">{{ latest ? `${latest.accuracy}%` : "—" }}</p>
          <p class="text-[11px] text-ink4 mt-1">{{ latest ? latest.grade : "Complete a mock to see a result" }}</p>
        </div>
        <div class="bg-bg-card border border-border rounded-2xl p-5">
          <p class="label-mono">Average accuracy</p>
          <p class="font-mono text-[28px] font-medium text-ink mt-2">{{ averageAccuracy === null ? "—" : `${averageAccuracy}%` }}</p>
        </div>
      </section>

      <section>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-[16px] font-semibold text-ink flex items-center gap-2"><History :size="16" class="text-ink3" /> Exam history</h3>
          <RouterLink to="/results" class="text-[13px] text-blue font-medium">View results</RouterLink>
        </div>
        <div v-if="recent.length" class="bg-bg-card border border-border rounded-2xl divide-y divide-border overflow-hidden">
          <div v-for="session in recent" :key="session.id" class="flex items-center gap-4 p-4">
            <div class="w-10 h-10 rounded-md bg-gold-light grid place-items-center shrink-0"><Trophy :size="16" class="text-gold" /></div>
            <div class="min-w-0 flex-1">
              <p class="text-[14px] font-medium text-ink truncate">{{ session.subject }} · {{ session.topic }}</p>
              <p class="text-[12px] text-ink3">{{ session.dateLabel }}</p>
            </div>
            <span class="font-mono text-[13px] text-ink font-semibold">{{ session.accuracy }}%</span>
            <span class="font-mono text-[11px] text-violet bg-violet-light rounded-full px-2 py-0.5">{{ session.grade }}</span>
          </div>
        </div>
        <div v-else class="bg-bg-card border border-border rounded-2xl p-7 text-center">
          <p class="text-[15px] font-semibold text-ink">No mock exams completed yet</p>
          <p class="text-[13px] text-ink3 mt-1">Your real exam-session history will appear here after submission.</p>
          <RouterLink to="/exam/session" class="inline-flex items-center gap-2 bg-gold text-white text-[13px] font-semibold px-4 py-2.5 rounded-md mt-4">Start first mock <ArrowRight :size="13" /></RouterLink>
        </div>
      </section>
    </div>
  </AppShell>
</template>
