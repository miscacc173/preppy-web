<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { Ring, Bars } from "@/components/app/visuals";
import ChordDiagram from "@/components/app/ChordDiagram.vue";
import { Download, FileText, Inbox } from "lucide-vue-next";
import { useSessions } from "@/lib/sessionStore";
import { GradeBadge } from "@/components/ui/grade-badge";
import { currentUser } from "@/lib/api";
import { learningSessionRepository } from "@/data/repositories/learningSessionRepository";
import type { UiTutorStudent } from "@/data/models/learningSession";
import type { SessionRecord } from "@/lib/sessionStore";

setPageMeta({ title: "Results — AiStar Prep" });

const SKILL_LABELS = ["Recall", "Diagram", "Apply", "Phrase", "Calculate", "Analyse"] as const;
const SKILL_COLORS = ["var(--blue)", "var(--violet)", "var(--mint)", "var(--gold)", "var(--pink)", "#9b87f5"];

const all = useSessions();
const isTeacher = computed(() => currentUser()?.roles?.some((role) => role.slug === "teacher") ?? false);
const teacherStudents = ref<UiTutorStudent[]>([]);
const selectedUserId = ref(0);
const selectedSessions = ref<SessionRecord[] | null>(null);
const loadingSessions = ref(false);
onMounted(async () => {
  if (!isTeacher.value) return;
  teacherStudents.value = await learningSessionRepository.teacherStudents().catch(() => []);
});
watch(selectedUserId, async (userId) => {
  if (!userId) { selectedSessions.value = null; return; }
  loadingSessions.value = true;
  try { selectedSessions.value = await learningSessionRepository.list(userId); }
  finally { loadingSessions.value = false; }
});
const sourceSessions = computed(() => selectedSessions.value ?? all.value);
const route = useRoute();
const focusId = computed(() => (typeof route.query.session === "string" ? route.query.session : undefined));

const sessions = computed(() => {
  const focused = focusId.value ? sourceSessions.value.filter((s) => s.id === focusId.value) : sourceSessions.value;
  return focused.length ? focused : sourceSessions.value;
});

const latest = computed(() => sessions.value[0]);
const rolling = computed(() => {
  const accuracies = sessions.value.map((s) => s.accuracy);
  return accuracies.length ? Math.round(accuracies.reduce((a, b) => a + b, 0) / accuracies.length) : 0;
});
const trend = computed(() => sessions.value.map((s) => s.accuracy).slice(0, 12).reverse());
const delta = computed(() => rolling.value - (trend.value[0] ?? rolling.value));

const topicRows = computed(() => {
  const agg = new Map<string, { earned: number; total: number }>();
  for (const s of sessions.value) {
    const key = `${s.subject} · ${s.topic}`;
    const cur = agg.get(key) ?? { earned: 0, total: 0 };
    cur.earned += s.earned;
    cur.total += s.totalMarks;
    agg.set(key, cur);
  }
  return Array.from(agg.entries())
    .map(([label, v]) => ({ label, value: v.total ? Math.round((v.earned / v.total) * 100) : 0 }))
    .sort((a, b) => b.value - a.value)
    .slice(0, 7)
    .map((r) => ({ ...r, color: (r.value >= 75 ? "mint" : r.value >= 60 ? "gold" : "pink") as "mint" | "gold" | "pink" }));
});

const strong = computed(() => topicRows.value.filter((r) => r.value >= 75).slice(0, 3));
const weak = computed(() => topicRows.value.filter((r) => r.value < 60).slice(0, 3));
const wrongInLatest = computed(() => latest.value?.questions.filter((q) => !q.correct).length ?? 0);

const skillChordNodes = computed(() => {
  if (!latest.value) return [];
  return SKILL_LABELS.map((label, i) => {
    const bucket = latest.value!.questions.filter((_, qi) => qi % SKILL_LABELS.length === i);
    const earned = bucket.reduce((s, q) => s + q.marksAwarded, 0);
    const total = bucket.reduce((s, q) => s + q.marks, 0);
    const value = total ? Math.round((earned / total) * 100) : latest.value!.accuracy;
    return { label, value, color: SKILL_COLORS[i] };
  });
});
function exportResults() { window.print(); }
</script>

<template>
  <AppShell title="Results" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div v-if="isTeacher" class="mb-5 rounded-2xl border border-border bg-bg-card p-4 print:hidden">
      <label class="block text-[11px] font-semibold uppercase tracking-wider text-ink4">Results owner</label>
      <select v-model.number="selectedUserId" class="ops-input mt-2 max-w-md">
        <option :value="0">My practice sessions</option>
        <option v-for="student in teacherStudents" :key="student.userId" :value="student.userId">{{ student.name }} · {{ student.course || student.subjects.join(', ') }}</option>
      </select>
      <p v-if="!teacherStudents.length" class="mt-2 text-[11px] text-ink3">No active institute students are assigned to your institute membership.</p>
    </div>
    <div v-if="loadingSessions" class="mb-5 h-24 animate-pulse rounded-2xl bg-bg-soft" />
    <div v-if="sessions.length === 0" class="max-w-[640px] mx-auto bg-bg-card border border-border rounded-2xl p-10 text-center">
      <Inbox :size="28" class="text-ink4 mx-auto mb-3" />
      <h2 class="text-[18px] font-semibold text-ink mb-1">No sessions yet</h2>
      <p class="text-[13px] text-ink3 mb-5">Complete a Learn or Exam session to see your performance analytics here.</p>
      <RouterLink to="/learn" class="inline-flex items-center gap-2 bg-violet text-white text-[13px] font-semibold px-5 h-11 rounded-md hover:opacity-90">
        Start a session
      </RouterLink>
    </div>

    <div v-else class="space-y-5">
      <div class="flex items-end justify-between flex-wrap gap-3">
        <div>
          <p class="label-mono">Performance report</p>
          <p class="text-[14px] text-ink3 font-light mt-1">
            {{ sessions.length }} session{{ sessions.length === 1 ? "" : "s" }} recorded · latest: {{ latest.subject }} · {{ latest.topic }}
          </p>
        </div>
        <button class="inline-flex items-center gap-2 text-[13px] text-ink2 bg-bg-card border border-border rounded-md px-4 py-2 hover:bg-bg-soft print:hidden" @click="exportResults">
          <Download :size="13" /> Export PDF
        </button>
      </div>

      <section class="grid grid-cols-1 lg:grid-cols-[1fr_1.4fr] gap-5">
        <div class="bg-bg-card border border-border rounded-2xl p-6 reveal-up">
          <p class="label-mono mb-4">Readiness gauge</p>
          <div class="grid place-items-center py-2">
            <Ring :value="rolling" :size="200" color="var(--mint)" label="Rolling accuracy" />
          </div>
          <div class="grid grid-cols-3 gap-2 mt-5">
            <div class="bg-bg-soft border border-border rounded-md p-2.5 text-center">
              <p class="label-mono">Latest grade</p>
              <p class="font-mono text-[18px] font-medium text-ink mt-1 flex items-center justify-center min-h-[28px]"><GradeBadge :grade="latest.grade" size="lg" /></p>
            </div>
            <div class="bg-bg-soft border border-border rounded-md p-2.5 text-center">
              <p class="label-mono">Latest score</p>
              <p class="font-mono text-[18px] font-medium text-ink mt-1 flex items-center justify-center min-h-[28px]">{{ latest.earned }}/{{ latest.totalMarks }}</p>
            </div>
            <div class="bg-bg-soft border border-border rounded-md p-2.5 text-center">
              <p class="label-mono">Mistakes</p>
              <p class="font-mono text-[18px] font-medium text-ink mt-1 flex items-center justify-center min-h-[28px]">{{ wrongInLatest }}/{{ latest.questions.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-bg-card border border-border rounded-2xl p-6 reveal-up flex flex-col">
          <div class="flex items-center justify-between mb-3">
            <p class="label-mono">Accuracy trend · last {{ trend.length }} session{{ trend.length === 1 ? "" : "s" }}</p>
            <span :class="`font-mono text-[11px] ${delta >= 0 ? 'text-mint' : 'text-pink'}`">{{ delta >= 0 ? "+" : "" }}{{ delta }}% vs first</span>
          </div>
          <div class="flex-1 grid place-items-center py-2">
            <ChordDiagram :nodes="skillChordNodes" :size="360" />
          </div>
        </div>
      </section>


      <section class="grid grid-cols-1 lg:grid-cols-[1fr_1fr] gap-5">
        <div class="bg-bg-card border border-border rounded-2xl p-6">
          <p class="label-mono mb-4">Topic mastery</p>
          <Bars v-if="topicRows.length" :rows="topicRows" />
          <p v-else class="text-[13px] text-ink3">Not enough data yet.</p>
        </div>

        <div class="bg-bg-card border border-border rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-border flex items-center gap-2">
            <FileText :size="14" class="text-ink4" />
            <p class="label-mono">Examiner report · auto-generated</p>
          </div>
          <div class="divide-y divide-border">
            <div class="p-5">
              <p class="text-[11px] font-semibold text-ink4 uppercase tracking-wider mb-2">Overall</p>
              <p class="text-[13px] text-ink2 leading-relaxed">
                Rolling accuracy is {{ rolling }}% across {{ sessions.length }} session{{ sessions.length === 1 ? "" : "s" }}.
                Latest session graded {{ latest.grade }} ({{ latest.earned }}/{{ latest.totalMarks }}).
              </p>
            </div>
            <div class="p-5">
              <p class="text-[11px] font-semibold text-ink4 uppercase tracking-wider mb-2">What scored marks</p>
              <p class="text-[13px] text-ink2 leading-relaxed">
                {{ strong.length ? `Strongest topics: ${strong.map((s) => `${s.label} (${s.value}%)`).join(", ")}.` : "Keep practicing — strong-topic data will appear here." }}
              </p>
            </div>
            <div class="p-5">
              <p class="text-[11px] font-semibold text-ink4 uppercase tracking-wider mb-2">What lost marks</p>
              <p class="text-[13px] text-ink2 leading-relaxed">
                {{ weak.length ? `Focus on: ${weak.map((s) => `${s.label} (${s.value}%)`).join(", ")}.` : "No weak topics flagged in current data." }}
              </p>
            </div>
          </div>
        </div>
      </section>

      <section>
        <h3 class="text-[15px] font-semibold text-ink mb-4">Past sessions</h3>
        <div class="bg-bg-card border border-border rounded-2xl overflow-hidden">
          <table class="w-full">
            <thead class="bg-bg-soft">
              <tr>
                <th class="text-left text-[11px] font-semibold text-ink4 uppercase tracking-wider px-5 py-3">Date</th>
                <th class="text-left text-[11px] font-semibold text-ink4 uppercase tracking-wider px-5 py-3">Session</th>
                <th class="text-left text-[11px] font-semibold text-ink4 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Type</th>
                <th class="text-left text-[11px] font-semibold text-ink4 uppercase tracking-wider px-5 py-3">Score</th>
                <th class="text-left text-[11px] font-semibold text-ink4 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Accuracy</th>
                <th class="text-left text-[11px] font-semibold text-ink4 uppercase tracking-wider px-5 py-3">Grade</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in sessions" :key="r.id" class="border-t border-border hover:bg-bg-soft">
                <td class="px-5 py-3.5 text-[13px] text-ink3 font-mono">{{ r.dateLabel }}</td>
                <td class="px-5 py-3.5 text-[13px] text-ink font-medium">{{ r.subject }} · {{ r.topic }}</td>
                <td class="px-5 py-3.5 text-[13px] text-ink3 hidden md:table-cell">
                  <span :class="`text-[11px] font-medium rounded-full px-2 py-0.5 ${r.type === 'Exam' ? 'bg-gold-light text-gold' : 'bg-violet-light text-violet'}`">{{ r.type }}</span>
                </td>
                <td class="px-5 py-3.5 text-[13px] text-ink font-mono">{{ r.earned }}/{{ r.totalMarks }}</td>
                <td class="px-5 py-3.5 text-[13px] text-ink font-mono hidden md:table-cell">{{ r.accuracy }}%</td>
                <td class="px-5 py-3.5"><GradeBadge :grade="r.grade" /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="text-center mt-4">
          <RouterLink to="/learn" class="text-[13px] text-blue font-medium">Train again →</RouterLink>
        </div>
      </section>
    </div>
  </AppShell>
</template>
