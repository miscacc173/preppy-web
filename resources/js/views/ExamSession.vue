<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import { useExamWizard } from "@/lib/wizardStore";
import { useSubscription } from "@/lib/subscription";
import { papersForSubject, SAMPLE_Q, type Paper, type Stage } from "./exam-session/data";
import { gradeFor, saveSession } from "@/lib/sessionStore";
import SetupStage from "./exam-session/SetupStage.vue";
import ActiveStage from "./exam-session/ActiveStage.vue";
import ReviewStage from "./exam-session/ReviewStage.vue";
import ResultsStage from "./exam-session/ResultsStage.vue";
import FeedbackStage from "./exam-session/FeedbackStage.vue";
import { learningSessionRepository } from "@/data/repositories/learningSessionRepository";

setPageMeta({ title: "Exam Session — AiStar Prep" });

const [subscription] = useSubscription();
const [examWiz, setExamWiz] = useExamWizard();

const paper = computed<Paper | null>(() => papersForSubject(examWiz.value.subject).find((p) => p.id === examWiz.value.paperId) ?? null);

function setSubject(v: string) {
  setExamWiz({ subject: v, paperId: "", level: subscription.value.level });
}
function setPaper(p: Paper | null) {
  setExamWiz({ paperId: p?.id ?? "" });
}

const stage = ref<Stage>("setup");
const questions = computed(() => SAMPLE_Q.slice(0, Math.min(SAMPLE_Q.length, paper.value?.questions ?? 8)));
const qIdx = ref(0);
const answers = ref<string[]>([]);
const flagged = ref<Set<number>>(new Set());
const secondsLeft = ref(0);
const sessionId = ref("");
const resultSaved = ref(false);
let interval: ReturnType<typeof setInterval> | null = null;
let progressTimer: ReturnType<typeof setTimeout> | null = null;
const startedAt = ref("");

const awarded = computed(() => questions.value.map((question, index) => {
  const answer = answers.value[index]?.trim() ?? "";
  if (!answer) return 0;
  return Math.min(question.marks, Math.max(1, Math.round((answer.length / Math.max(80, question.model.length * 0.6)) * question.marks)));
}));
const totalMarks = computed(() => questions.value.reduce((sum, question) => sum + question.marks, 0));
const earned = computed(() => awarded.value.reduce((sum, marks) => sum + marks, 0));
const accuracy = computed(() => totalMarks.value ? Math.round((earned.value / totalMarks.value) * 100) : 0);

watch(stage, (s) => {
  if (interval) clearInterval(interval);
  if (s === "active") {
    interval = setInterval(() => {
      if (secondsLeft.value <= 1) {
        if (interval) clearInterval(interval);
        secondsLeft.value = 0;
        stage.value = "results";
      } else {
        secondsLeft.value -= 1;
      }
    }, 1000);
  }
  if (s === "results" && !resultSaved.value) { if (progressTimer) clearTimeout(progressTimer); persistResult(); }
});
watch([stage, answers, secondsLeft, qIdx], () => {
  if (stage.value !== "active" || !sessionId.value || !paper.value) return;
  if (progressTimer) clearTimeout(progressTimer);
  progressTimer = setTimeout(() => {
    void learningSessionRepository.saveProgress(sessionId.value, { session_type: "exam", level: subscription.value.level, subject: examWiz.value.subject, topic: paper.value?.name || null, time_seconds: Math.max(0, (paper.value?.minutes || 0) * 60 - secondsLeft.value), started_at: startedAt.value, questions: questions.value.map((question, index) => ({ q: question.q, marks: question.marks, given: answers.value[index] || null })), metadata: { question_source: "past-papers", scoring_source: "local", progress_question_index: qIdx.value } }).catch(() => undefined);
  }, 1500);
}, { deep: true });
onBeforeUnmount(() => {
  if (interval) clearInterval(interval);
  if (progressTimer) clearTimeout(progressTimer);
});

function start() {
  if (!paper.value) return;
  answers.value = Array(questions.value.length).fill("");
  flagged.value = new Set();
  qIdx.value = 0;
  secondsLeft.value = paper.value.minutes * 60;
  sessionId.value = `exam-${Date.now()}`;
  startedAt.value = new Date().toISOString();
  resultSaved.value = false;
  stage.value = "active";
  void learningSessionRepository.saveProgress(sessionId.value, {
    session_type: "exam",
    level: subscription.value.level,
    subject: examWiz.value.subject,
    topic: paper.value.name,
    time_seconds: 0,
    started_at: startedAt.value,
    questions: questions.value.map((question) => ({ q: question.q, marks: question.marks, model: question.model })),
    metadata: { question_source: "past-papers", scoring_source: "local", progress_question_index: 0 },
  }).catch(() => undefined);
}

function persistResult() {
  if (!paper.value || !sessionId.value) return;
  const usedSeconds = Math.max(0, paper.value.minutes * 60 - secondsLeft.value);
  saveSession({
    id: sessionId.value,
    level: subscription.value.level,
    subject: examWiz.value.subject,
    topic: paper.value.name,
    type: "Exam",
    earned: earned.value,
    totalMarks: totalMarks.value,
    accuracy: accuracy.value,
    grade: gradeFor(accuracy.value),
    timeSec: usedSeconds,
    avgPerQ: questions.value.length ? Math.round(usedSeconds / questions.value.length) : 0,
    questions: questions.value.map((question, index) => ({
      q: question.q,
      type: "short",
      marks: question.marks,
      marksAwarded: awarded.value[index],
      correct: awarded.value[index] === question.marks,
      skipped: !answers.value[index]?.trim(),
      given: answers.value[index]?.trim() || null,
      starAnswer: question.model,
      timeSec: questions.value.length ? Math.round(usedSeconds / questions.value.length) : 0,
    })),
    metadata: { question_source: "past-papers", scoring_source: "local" },
  });
  resultSaved.value = true;
}

function toggleFlag() {
  const next = new Set(flagged.value);
  if (next.has(qIdx.value)) next.delete(qIdx.value);
  else next.add(qIdx.value);
  flagged.value = next;
}

function updateAnswer(text: string) {
  answers.value = answers.value.map((a, i) => (i === qIdx.value ? text : a));
}
</script>

<template>
  <SetupStage
    v-if="stage === 'setup'"
    :subjects="subscription.subjects"
    :level="subscription.level"
    :exam-board="subscription.examBoard"
    :subject="examWiz.subject"
    :paper="paper"
    :answer-method="examWiz.answerMethod"
    :instant-explanation="examWiz.instantExplanation"
    :feedback-depth="examWiz.feedbackDepth"
    @update:subject="setSubject"
    @update:paper="setPaper"
    @update:answerMethod="(v) => setExamWiz({ answerMethod: v })"
    @update:instantExplanation="(v) => setExamWiz({ instantExplanation: v })"
    @update:feedbackDepth="(v) => setExamWiz({ feedbackDepth: v })"
    @start="start"
  />

  <ActiveStage
    v-else-if="stage === 'active'"
    :questions="questions"
    :session-id="sessionId"
    :q-idx="qIdx"
    :answers="answers"
    :flagged="flagged"
    :seconds-left="secondsLeft"
    :answer-method="examWiz.answerMethod"
    @update:q-idx="qIdx = $event"
    @update:answer="updateAnswer"
    @toggle-flag="toggleFlag"
    @review="stage = 'review'"
  />

  <ReviewStage
    v-else-if="stage === 'review'"
    :questions="questions"
    :answers="answers"
    :flagged="flagged"
    :seconds-left="secondsLeft"
    @back="stage = 'active'"
    @goto-question="(i) => { qIdx = i; stage = 'active'; }"
    @submit="stage = 'results'"
  />

  <ResultsStage
    v-else-if="stage === 'results'"
    :subject="examWiz.subject"
    :paper="paper"
    :questions="questions"
    :answers="answers"
    :flagged="flagged"
    :seconds-left="secondsLeft"
    :earned="earned"
    :total-marks="totalMarks"
    :accuracy="accuracy"
    :grade="gradeFor(accuracy)"
    @view-feedback="stage = 'feedback'"
    @attempt-another="stage = 'setup'"
  />

  <FeedbackStage
    v-else
    :subject="examWiz.subject"
    :paper="paper"
    :questions="questions"
    :answers="answers"
    :session-id="sessionId"
    @back="stage = 'results'"
    @attempt-another="stage = 'setup'"
  />
</template>
