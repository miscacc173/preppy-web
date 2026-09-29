<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Activity, BookOpen, CalendarDays, CheckCircle2, Clock3, GraduationCap, LoaderCircle, School, ShieldCheck, Target, Trophy, UserRound } from "lucide-vue-next";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import { parentRepository } from "@/data/repositories/parentRepository";
import type { UiParentProgressSession, UiParentStudent, UiParentSubjectProgress } from "@/data/models/parent";

const links = ref<UiParentStudent[]>([]);
const loading = ref(true);
const error = ref("");

onMounted(async () => {
  try {
    links.value = await parentRepository.students();
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Unable to load linked students.";
  } finally {
    loading.value = false;
  }
});

function subjectProgress(link: UiParentStudent): UiParentSubjectProgress[] {
  const performance = link.performance;
  if (!performance) return [];
  const details = new Map(performance.subjectReadiness.map((item) => [item.subject, item]));
  const subjects = [...new Set(link.student.subjects)];

  return subjects.map((subject) => {
    const stored = details.get(subject);
    if (stored) {
      const fallbackSessions = [...performance.activeSessions, ...performance.completedSessions]
        .filter((session) => session.subject === subject)
        .slice(0, 12);
      return { ...stored, recentSessions: stored.recentSessions.length ? stored.recentSessions : fallbackSessions };
    }
    return {
      subject, readiness: 0, hasActivity: false, earned: 0, totalMarks: 0,
      completedSessions: 0, activeSessions: 0, averageScore: null, bestScore: null,
      latestScore: null, totalMinutes: 0, lastSessionAt: "", recentSessions: [],
    };
  });
}

function totalStudyMinutes(link: UiParentStudent) {
  return link.performance?.subjectReadiness.reduce((total, subject) => total + subject.totalMinutes, 0) ?? 0;
}

function sessionDate(session: UiParentProgressSession) {
  return session.completedAt || session.startedAt;
}

function formatDate(value: string, withTime = true) {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "—";
  return new Intl.DateTimeFormat("en-GB", withTime
    ? { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }
    : { day: "2-digit", month: "short", year: "numeric" }).format(date);
}

function formatDuration(seconds: number) {
  if (!seconds) return "0 min";
  const minutes = Math.max(1, Math.round(seconds / 60));
  return minutes < 60 ? `${minutes} min` : `${Math.floor(minutes / 60)}h ${minutes % 60}m`;
}

function modeLabel(type: string) {
  return type === "exam" ? "Exam" : "Learn";
}
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <TopNav />
    <main class="mx-auto w-full max-w-[1280px] flex-1 px-5 py-10">
      <p class="label-mono text-blue">Parent portal</p>
      <h1 class="mt-2 text-[30px] font-bold text-ink">Student progress</h1>
      <p class="mt-1 text-[13px] text-ink3">Subject readiness, marks, session dates, and recent learning activity for every linked student.</p>

      <div v-if="loading" class="mt-8 grid place-items-center rounded-2xl border border-border bg-bg-card py-16">
        <LoaderCircle :size="22" class="animate-spin text-blue" />
      </div>
      <p v-else-if="error" class="mt-6 rounded-xl bg-pink-light p-4 text-[13px] text-pink">{{ error }}</p>
      <div v-else-if="!links.length" class="mt-8 rounded-2xl border border-dashed border-border bg-bg-card p-10 text-center text-[13px] text-ink3">
        No students are linked to this parent account yet.
      </div>

      <div v-else class="mt-8 space-y-6">
        <article v-for="(link, studentIndex) in links" :key="link.id" class="overflow-hidden rounded-2xl border border-border bg-bg-card shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border p-5 md:p-6">
            <div class="flex items-center gap-3">
              <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-blue-light text-blue"><UserRound :size="20" /></div>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-blue">Student {{ studentIndex + 1 }} of {{ links.length }}</p>
                <h2 class="mt-0.5 text-[20px] font-bold text-ink">Student: {{ link.student.name }}</h2>
                <p class="mt-1 text-[12px] text-ink3">{{ link.relationship }}<span v-if="link.student.institute"> · {{ link.student.institute }}</span></p>
              </div>
            </div>
            <StatusBadge :value="link.status" />
          </div>

          <div v-if="link.status !== 'active'" class="m-5 rounded-xl bg-gold-light p-4 text-[12px] text-gold">
            Activation is pending. Student information remains hidden until the invitation is accepted.
          </div>
          <div v-else class="space-y-6 p-5 md:p-6">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div class="rounded-xl bg-bg-soft p-4">
                <GraduationCap :size="16" class="text-blue" />
                <p class="mt-2 text-[11px] text-ink3">Course</p>
                <p class="text-[13px] font-semibold">{{ link.student.course || 'Not set' }}</p>
              </div>
              <div class="rounded-xl bg-bg-soft p-4">
                <BookOpen :size="16" class="text-violet" />
                <p class="mt-2 text-[11px] text-ink3">Exam board</p>
                <p class="text-[13px] font-semibold">{{ link.student.examBoard || 'Not set' }}</p>
              </div>
              <div class="rounded-xl bg-bg-soft p-4">
                <School :size="16" class="text-mint" />
                <p class="mt-2 text-[11px] text-ink3">School</p>
                <p class="text-[13px] font-semibold">{{ link.student.schoolName || 'Not set' }}</p>
              </div>
              <div class="rounded-xl bg-bg-soft p-4">
                <CalendarDays :size="16" class="text-gold" />
                <p class="mt-2 text-[11px] text-ink3">School year</p>
                <p class="text-[13px] font-semibold">{{ link.student.schoolYear || 'Not set' }}</p>
              </div>
            </div>

            <template v-if="link.performance">
              <section>
                <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-mint">
                  <ShieldCheck :size="14" /> Performance overview
                </div>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                  <div class="rounded-xl border border-border p-4"><Target :size="16" class="text-blue" /><p class="mt-3 text-[11px] text-ink3">Overall readiness</p><strong class="text-[22px]">{{ link.performance.overallReadiness ?? '—' }}<span v-if="link.performance.overallReadiness !== null" class="text-[13px]">%</span></strong></div>
                  <div class="rounded-xl border border-border p-4"><Activity :size="16" class="text-violet" /><p class="mt-3 text-[11px] text-ink3">Active sessions</p><strong class="text-[22px]">{{ link.performance.activeSessions.length }}</strong></div>
                  <div class="rounded-xl border border-border p-4"><CheckCircle2 :size="16" class="text-mint" /><p class="mt-3 text-[11px] text-ink3">Completed sessions</p><strong class="text-[22px]">{{ link.performance.completedSessions.length }}</strong></div>
                  <div class="rounded-xl border border-border p-4"><Clock3 :size="16" class="text-gold" /><p class="mt-3 text-[11px] text-ink3">Completed study time</p><strong class="text-[22px]">{{ totalStudyMinutes(link) }}<span class="text-[13px]"> min</span></strong></div>
                </div>
              </section>

              <section>
                <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                  <div><h2 class="text-[17px] font-bold text-ink">Subject performance</h2><p class="mt-1 text-[11px] text-ink3">Readiness and session history are calculated from completed Learn and Exam sessions.</p></div>
                  <span class="text-[11px] text-ink3">{{ link.student.subjects.length }} active-plan subject(s)</span>
                </div>

                <div v-if="!subjectProgress(link).length" class="rounded-xl border border-dashed border-border p-6 text-center text-[12px] text-ink3">No subjects are available in this student's active plan.</div>
                <div v-else class="space-y-3">
                  <details v-for="(subject, subjectIndex) in subjectProgress(link)" :key="subject.subject" :open="subjectIndex === 0" class="group overflow-hidden rounded-xl border border-border">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-4 p-4 transition hover:bg-bg-soft">
                      <div class="min-w-[180px] flex-1">
                        <div class="flex items-center gap-2"><BookOpen :size="15" class="text-violet" /><strong class="text-[14px]">{{ subject.subject }}</strong></div>
                        <div class="mt-2 h-1.5 max-w-[300px] overflow-hidden rounded-full bg-bg-soft"><div class="h-full rounded-full bg-violet" :style="{ width: `${subject.hasActivity ? subject.readiness : 0}%` }" /></div>
                      </div>
                      <div class="grid grid-cols-3 gap-5 text-right text-[11px] sm:gap-8">
                        <div><span class="block text-ink3">Readiness</span><strong class="text-[15px]">{{ subject.hasActivity ? `${subject.readiness}%` : '—' }}</strong></div>
                        <div><span class="block text-ink3">Completed</span><strong class="text-[15px]">{{ subject.completedSessions }}</strong></div>
                        <div><span class="block text-ink3">Latest</span><strong class="text-[15px]">{{ subject.latestScore === null ? '—' : `${subject.latestScore}%` }}</strong></div>
                      </div>
                    </summary>

                    <div class="border-t border-border bg-bg-soft/40 p-4">
                      <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="rounded-lg bg-bg-card p-3"><p class="text-[10px] text-ink3">Average score</p><strong class="text-[14px]">{{ subject.averageScore === null ? '—' : `${subject.averageScore}%` }}</strong></div>
                        <div class="rounded-lg bg-bg-card p-3"><p class="text-[10px] text-ink3">Best score</p><strong class="text-[14px]">{{ subject.bestScore === null ? '—' : `${subject.bestScore}%` }}</strong></div>
                        <div class="rounded-lg bg-bg-card p-3"><p class="text-[10px] text-ink3">Marks earned</p><strong class="text-[14px]">{{ subject.totalMarks ? `${subject.earned}/${subject.totalMarks}` : '—' }}</strong></div>
                        <div class="rounded-lg bg-bg-card p-3"><p class="text-[10px] text-ink3">Study time</p><strong class="text-[14px]">{{ subject.totalMinutes }} min</strong></div>
                        <div class="rounded-lg bg-bg-card p-3"><p class="text-[10px] text-ink3">Last session</p><strong class="text-[12px]">{{ formatDate(subject.lastSessionAt, false) }}</strong></div>
                      </div>

                      <div v-if="subject.recentSessions.length" class="mt-4 overflow-x-auto rounded-lg border border-border bg-bg-card">
                        <table class="w-full min-w-[900px] text-left text-[11px]">
                          <thead class="bg-bg-soft text-ink3"><tr><th class="px-3 py-2.5">Date</th><th class="px-3 py-2.5">Mode</th><th class="px-3 py-2.5">Topic / paper</th><th class="px-3 py-2.5">Score</th><th class="px-3 py-2.5">Grade</th><th class="px-3 py-2.5">Marks</th><th class="px-3 py-2.5">Questions</th><th class="px-3 py-2.5">Duration</th><th class="px-3 py-2.5">Status</th></tr></thead>
                          <tbody class="divide-y divide-border">
                            <tr v-for="session in subject.recentSessions" :key="session.id" class="hover:bg-bg-soft/70">
                              <td class="whitespace-nowrap px-3 py-3">{{ formatDate(sessionDate(session)) }}</td>
                              <td class="px-3 py-3"><span class="rounded-full bg-blue-light px-2 py-1 font-semibold text-blue">{{ modeLabel(session.type) }}</span></td>
                              <td class="max-w-[220px] px-3 py-3"><span class="block truncate">{{ session.topic || 'General practice' }}</span></td>
                              <td class="px-3 py-3 font-semibold">{{ session.status === 'completed' ? `${session.score}%` : '—' }}</td>
                              <td class="px-3 py-3 font-semibold">{{ session.grade || '—' }}</td>
                              <td class="px-3 py-3">{{ session.totalMarks ? `${session.earned}/${session.totalMarks}` : '—' }}</td>
                              <td class="px-3 py-3">{{ session.questionCount || '—' }}<span v-if="session.questionCount" class="block text-[10px] text-ink3">{{ session.answeredCount }} answered · {{ session.correctCount }} correct</span></td>
                              <td class="whitespace-nowrap px-3 py-3">{{ formatDuration(session.timeSeconds) }}</td>
                              <td class="px-3 py-3"><StatusBadge :value="session.status" /></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div v-else class="mt-4 rounded-lg border border-dashed border-border bg-bg-card p-5 text-center text-[11px] text-ink3">No sessions recorded for this subject yet.</div>
                    </div>
                  </details>
                </div>
              </section>

              <section v-if="link.performance.dailyProgress.length">
                <h2 class="text-[17px] font-bold text-ink">Recent daily activity</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                  <div v-for="day in link.performance.dailyProgress.slice(0, 8)" :key="day.date" class="rounded-xl border border-border p-3">
                    <p class="text-[11px] font-semibold">{{ formatDate(day.date, false) }}</p>
                    <p class="mt-2 text-[10px] text-ink3">{{ day.sessions }} session(s) · {{ day.minutes }} min</p>
                    <p class="mt-1 text-[12px] font-semibold text-mint">{{ day.averageScore }}% average</p>
                  </div>
                </div>
              </section>
            </template>

            <div v-else class="rounded-xl border border-dashed border-border p-6 text-center text-[12px] text-ink3">Performance access is not enabled for this student link.</div>

            <div v-if="link.subscription" class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-bg-soft p-4 text-[12px]">
              <span><Trophy :size="14" class="mr-2 inline text-gold" />{{ link.subscription.plan || 'Subscription' }}<span v-if="link.subscription.periodEnd" class="ml-2 text-ink3">Renews/ends {{ formatDate(link.subscription.periodEnd, false) }}</span></span><StatusBadge :value="link.subscription.status" />
            </div>
          </div>
        </article>
      </div>
    </main>
    <Footer />
  </div>
</template>
