<script setup lang="ts">
import { computed } from "vue";
import AppShell from "@/components/app/AppShell.vue";
import { QuestionVisual } from "@/components/app/question-visual";
import { GradeBadge } from "@/components/ui/grade-badge";
import { ArrowRight, RotateCcw, FileText, ListChecks } from "lucide-vue-next";
import { gradeFor } from "@/lib/sessionStore";
import { fmtTime, type AnswerRecord, type Question } from "./data";
import TutorQuestionFeedback from "@/components/app/TutorQuestionFeedback.vue";

const props = defineProps<{
  subject: string;
  topics: string[];
  questions: Question[];
  answers: AnswerRecord[];
  sessionSec: number;
  lastSavedId: string | null;
}>();

const emit = defineEmits<{ "retry-question": [index: number]; "train-again": [] }>();

const totalMarks = computed(() => props.questions.reduce((s, q) => s + q.marks, 0));
const earned = computed(() => props.answers.reduce((s, a) => s + a.marksAwarded, 0));
const accuracy = computed(() => (totalMarks.value ? Math.round((earned.value / totalMarks.value) * 100) : 0));
const grade = computed(() => gradeFor(accuracy.value));
const topicLabel = computed(() => (props.topics.length === 0 ? "All topics" : props.topics.length === 1 ? props.topics[0] : `${props.topics.length} topics`));
const avgPerQ = computed(() => (props.answers.length ? Math.round(props.answers.reduce((s, a) => s + a.timeSec, 0) / props.answers.length) : 0));

function feedbackFor(a: AnswerRecord, q: Question) {
  const correct = a.marksAwarded === q.marks;
  if (correct) return "Strong answer — captures all mark points.";
  if (a.marksAwarded > 0) return "Partial credit. Missed a key phrase or step from the mark scheme.";
  if (a.given === null) return "Skipped — revisit this topic.";
  return "Off mark scheme. Review the A* answer.";
}
function answerText(a: AnswerRecord, q: Question) {
  if (a.given === null) return "";
  return q.type === "mcq" ? q.opts?.[a.given as number] ?? String(a.given) : String(a.given);
}
</script>

<template>
  <AppShell title="Session review">
    <div class="max-w-[1000px] mx-auto space-y-5">
      <div class="bg-bg-card border border-border rounded-2xl p-6 reveal-up">
        <p class="label-mono mb-3">Results · {{ subject }} · {{ topicLabel }}</p>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <div class="bg-bg-soft border border-border rounded-lg p-3">
            <p class="label-mono">Score</p>
            <p class="font-mono text-[22px] font-medium mt-1 text-ink flex items-center min-h-[28px]">{{ earned }}/{{ totalMarks }}</p>
          </div>
          <div class="bg-bg-soft border border-border rounded-lg p-3">
            <p class="label-mono">Grade</p>
            <p class="font-mono text-[22px] font-medium mt-1 text-ink flex items-center min-h-[28px]"><GradeBadge :grade="grade" size="lg" /></p>
          </div>
          <div class="bg-bg-soft border border-border rounded-lg p-3">
            <p class="label-mono">Accuracy</p>
            <p class="font-mono text-[22px] font-medium mt-1 text-ink flex items-center min-h-[28px]">{{ accuracy }}%</p>
          </div>
          <div class="bg-bg-soft border border-border rounded-lg p-3">
            <p class="label-mono">Time taken</p>
            <p class="font-mono text-[22px] font-medium mt-1 text-ink flex items-center min-h-[28px]">{{ fmtTime(sessionSec) }}</p>
          </div>
          <div class="bg-bg-soft border border-border rounded-lg p-3">
            <p class="label-mono">Avg/Q</p>
            <p class="font-mono text-[22px] font-medium mt-1 text-ink flex items-center min-h-[28px]">{{ avgPerQ }}s</p>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <div v-for="(q, i) in questions" :key="i" class="bg-bg-card border border-border rounded-2xl p-6">
          <div class="flex items-start justify-between mb-3 flex-wrap gap-3">
            <div>
              <p class="label-mono mb-1">Question {{ i + 1 }} · {{ q.marks }} marks</p>
              <h3 class="text-[16px] font-semibold text-ink leading-snug">{{ q.q }}</h3>
            </div>
            <span
              :class="`font-mono text-[12px] px-3 py-1 rounded-full border ${
                answers[i].marksAwarded === q.marks
                  ? 'bg-mint-light text-mint border-mint-light'
                  : answers[i].marksAwarded > 0
                    ? 'bg-gold-light text-gold border-gold-light'
                    : 'bg-pink-light text-pink border-pink-light'
              }`"
            >
              {{ answers[i].marksAwarded }}/{{ q.marks }}
            </span>
          </div>
          <QuestionVisual v-if="q.visual" :visual="q.visual" :figure-number="i + 1" />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
            <div class="bg-bg-soft border border-border rounded-lg p-4">
              <p class="text-[11px] font-semibold text-ink4 uppercase tracking-wider mb-2">Your answer</p>
              <p class="text-[13px] text-ink2 leading-relaxed">
                <span v-if="answers[i].given === null" class="italic text-ink3">Skipped</span>
                <template v-else-if="q.type === 'mcq'">{{ q.opts![answers[i].given as number] }}</template>
                <template v-else>
                  <span v-if="!String(answers[i].given)" class="italic text-ink3">No answer</span>
                  <template v-else>{{ answers[i].given }}</template>
                </template>
              </p>
            </div>
            <div class="bg-violet-light/40 border border-violet-light rounded-lg p-4">
              <p class="text-[11px] font-semibold text-violet uppercase tracking-wider mb-2">A* answer</p>
              <p class="text-[13px] text-ink leading-relaxed">{{ q.starAnswer }}</p>
            </div>
          </div>

          <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="bg-bg-soft border border-border rounded-lg p-4">
              <p class="text-[11px] font-semibold text-blue uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <ListChecks :size="12" /> Mark scheme
              </p>
              <ul class="space-y-1 text-[12.5px] text-ink2">
                <li v-for="(m, j) in q.markScheme" :key="j">• {{ m }}</li>
              </ul>
            </div>
            <div class="bg-bg-soft border-l-[3px] border-l-violet border border-border rounded-lg p-4">
              <p class="text-[11px] font-semibold text-violet uppercase tracking-wider mb-2">Examiner feedback</p>
              <p class="text-[12.5px] text-ink2 italic leading-relaxed">{{ feedbackFor(answers[i], q) }}</p>
              <p class="text-[12.5px] text-ink2 mt-2"><strong class="text-ink">Improve:</strong> {{ q.hints[0] }}</p>
            </div>
          </div>

          <div class="mt-4 flex gap-2 flex-wrap">
            <button
              class="inline-flex items-center gap-2 text-[13px] font-semibold bg-violet-light text-violet border border-violet-light rounded-md h-10 px-4 hover:bg-violet-light/70"
              @click="emit('retry-question', i)"
            >
              <RotateCcw :size="13" /> Retry question
            </button>
          </div>
          <TutorQuestionFeedback v-if="lastSavedId" session-type="learn" :session-id="lastSavedId" :question-key="`learn-${i}`" :question-text="q.q" :answer-text="answerText(answers[i], q)" />
        </div>
      </div>

      <div class="bg-bg-card border border-border rounded-2xl p-5 flex flex-wrap gap-3 justify-between items-center">
        <p class="text-[13px] text-ink3">Session complete. Push for A* on weak topics.</p>
        <div class="flex gap-2 flex-wrap">
          <RouterLink
            :to="{ path: '/results', query: lastSavedId ? { session: lastSavedId } : undefined }"
            class="inline-flex items-center gap-2 bg-bg-soft border border-border text-ink2 text-[13px] font-semibold px-5 h-11 rounded-md hover:bg-bg-soft2"
          >
            <FileText :size="14" /> Full analytics
          </RouterLink>
          <button class="inline-flex items-center gap-2 bg-violet text-white text-[13px] font-semibold px-5 h-11 rounded-md hover:opacity-90" @click="emit('train-again')">
            Train again <ArrowRight :size="14" />
          </button>
        </div>
      </div>
    </div>
  </AppShell>
</template>
