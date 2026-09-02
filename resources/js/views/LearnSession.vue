<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { Loader2 } from "lucide-vue-next";
import { saveSession, gradeFor } from "@/lib/sessionStore";
import {
  saveInProgress, getInProgressById, getAllInProgress, clearInProgress, newSessionId,
  type InProgressSession, type PersistedQuestion,
} from "@/lib/learnSessionStore";
import { useLearnWizard, LEARN_QTYPE_LABELS, type LearnDifficulty, type LearnQTypeLabel } from "@/lib/wizardStore";
import { useSubscription } from "@/lib/subscription";
import { currentUser, defaultAuthenticatedPath } from "@/lib/api";
import { learningSessionRepository } from "@/data/repositories/learningSessionRepository";
import { BANK, MCQ_LABELS, type AnswerRecord, type Question, type Stage } from "./learn-session/data";
import SetupStage from "./learn-session/SetupStage.vue";
import SessionStage from "./learn-session/SessionStage.vue";
import ReviewStage from "./learn-session/ReviewStage.vue";

setPageMeta({ title: "Learn Session — AiStar Prep" });

const [subscription] = useSubscription();
const [wiz, setWiz] = useLearnWizard();
const router = useRouter();
const route = useRoute();

function toggleTopic(t: string) {
  setWiz((prev) => ({ ...prev, topics: prev.topics.includes(t) ? prev.topics.filter((x) => x !== t) : [...prev.topics, t] }));
}
function toggleQType(q: LearnQTypeLabel) {
  setWiz((prev) => ({ ...prev, qtypes: prev.qtypes.includes(q) ? prev.qtypes.filter((x) => x !== q) : [...prev.qtypes, q] }));
}

// Dashboard subject navigation preselects only an allowed subject. Resume
// parameters always take priority over a dashboard preselection.
watch(
  [() => subscription.value.subjects.join("|"), () => route.query.subject],
  () => {
    if (wiz.value.subject && !subscription.value.subjects.includes(wiz.value.subject)) {
      setWiz({ subject: "", topics: [] });
    }

    const routeSubject = route.query.subject;
    if (route.query.resume || route.query.sid || typeof routeSubject !== "string") return;
    const requestedName = routeSubject.trim().toLocaleLowerCase();
    const requested = subscription.value.subjects.find(
      (subject) => subject.toLocaleLowerCase() === requestedName,
    );
    if (requested && wiz.value.subject !== requested) {
      setWiz({ subject: requested, topics: [] });
    }
  },
  { immediate: true },
);

const stage = ref<Stage>("setup");

const qIdx = ref(0);
const answers = ref<AnswerRecord[]>([]);
const currentText = ref("");
const currentOpt = ref<number | null>(null);
const hintsShown = ref(0);
const showSample = ref(false);
let questionStart = 0;

const sessionSec = ref(0);
const practiceLeft = ref(0);
let sessionTick: ReturnType<typeof setInterval> | null = null;
let practiceTick: ReturnType<typeof setInterval> | null = null;
let progressSync: ReturnType<typeof setTimeout> | null = null;

const questions = ref<Question[]>([]);
const exitOpen = ref(false);
const lastSavedId = ref<string | null>(null);

let sessionId: string | null = null;
let startedAt: string | null = null;

// Resume in-progress session on mount (targeted by ?sid=... or ?resume=1).
onMounted(() => {
  const sid = typeof route.query.sid === "string" ? route.query.sid : undefined;
  const resume = route.query.resume;

  let saved: InProgressSession | null = null;
  if (sid) saved = getInProgressById(sid);
  else if (resume) saved = getAllInProgress()[0] ?? null;

  if (saved && saved.kind === "Learn") {
    sessionId = saved.id;
    startedAt = saved.startedAt;

    setWiz({
      level: saved.level,
      subject: saved.subject,
      topics: saved.topics,
      qtypes: [],
      difficulty: saved.difficulty as LearnDifficulty,
      count: saved.count,
      practiceMin: saved.practiceMin,
    });
    questions.value = saved.questions as unknown as Question[];
    answers.value = saved.answers as unknown as AnswerRecord[];
    qIdx.value = saved.qIdx;
    currentText.value = saved.currentText;
    currentOpt.value = saved.currentOpt;
    hintsShown.value = saved.hintsShown;
    showSample.value = saved.showSample;
    sessionSec.value = saved.sessionSec;
    practiceLeft.value = saved.practiceLeft;
    questionStart = Date.now();
    stage.value = "session";
  }
});

// Persist active session on every meaningful change under its own id.
watch([qIdx, answers, currentText, currentOpt, hintsShown, showSample, sessionSec, practiceLeft], () => {
  if (stage.value !== "session" || questions.value.length === 0) return;
  if (!sessionId) sessionId = newSessionId();
  if (!startedAt) startedAt = new Date().toISOString();
  const snapshot: InProgressSession = {
    id: sessionId,
    kind: "Learn",
    level: subscription.value.level,
    subject: wiz.value.subject,
    topics: wiz.value.topics,
    difficulty: wiz.value.difficulty,
    practiceMin: wiz.value.practiceMin,
    count: wiz.value.count,
    questions: questions.value as unknown as PersistedQuestion[],
    answers: answers.value,
    qIdx: qIdx.value,
    currentText: currentText.value,
    currentOpt: currentOpt.value,
    hintsShown: hintsShown.value,
    showSample: showSample.value,
    sessionSec: sessionSec.value,
    practiceLeft: practiceLeft.value,
    startedAt,
    updatedAt: new Date().toISOString(),
  };
  saveInProgress(snapshot);
  if (progressSync) clearTimeout(progressSync);
  progressSync = setTimeout(() => {
    if (!sessionId) return;
    void learningSessionRepository.saveProgress(sessionId, {
      session_type: "learn",
      level: subscription.value.level,
      subject: wiz.value.subject,
      topic: wiz.value.topics.join(", ") || null,
      time_seconds: sessionSec.value,
      started_at: startedAt,
      questions: questions.value.map((question) => ({ q: question.q, marks: question.marks, star_answer: question.starAnswer })),
      metadata: { question_source: wiz.value.questionSource === "ai" ? "ai" : wiz.value.questionSource, progress_question_index: qIdx.value },
    }).catch(() => undefined);
  }, 750);
}, { deep: true });

watch(stage, (s) => {
  if (sessionTick) clearInterval(sessionTick);
  if (practiceTick) clearInterval(practiceTick);
  if (s === "session") {
    sessionTick = setInterval(() => (sessionSec.value += 1), 1000);
    if (wiz.value.practiceMin > 0) {
      practiceTick = setInterval(() => {
        if (practiceLeft.value <= 1) {
          if (practiceTick) clearInterval(practiceTick);
          practiceLeft.value = 0;
          finishSession();
        } else {
          practiceLeft.value -= 1;
        }
      }, 1000);
    }
  }
});
onBeforeUnmount(() => {
  if (sessionTick) clearInterval(sessionTick);
  if (practiceTick) clearInterval(practiceTick);
  if (progressSync) clearTimeout(progressSync);
});

function startSession() {
  sessionId = newSessionId();
  startedAt = new Date().toISOString();
  const wantsMCQ = wiz.value.qtypes.length === 0 || wiz.value.qtypes.some((q) => (MCQ_LABELS as readonly string[]).includes(q));
  const wantsShort = wiz.value.qtypes.length === 0 || wiz.value.qtypes.some((q) => !(MCQ_LABELS as readonly string[]).includes(q));
  const pool = BANK.filter((q) => (q.type === "mcq" ? wantsMCQ : wantsShort));
  const source = pool.length ? pool : BANK;
  const chosen: Question[] = [];
  for (let i = 0; i < wiz.value.count; i++) chosen.push(source[i % source.length]);
  questions.value = chosen;
  answers.value = chosen.map(() => ({ given: null, skipped: false, marksAwarded: 0, timeSec: 0 }));
  qIdx.value = 0;
  currentText.value = "";
  currentOpt.value = null;
  hintsShown.value = 0;
  showSample.value = false;
  questionStart = Date.now();
  sessionSec.value = 0;
  practiceLeft.value = wiz.value.practiceMin * 60;
  stage.value = "session";
}

function recordAnswer(skipped: boolean) {
  const q = questions.value[qIdx.value];
  const timeSec = Math.round((Date.now() - questionStart) / 1000);
  let given: string | number | null = null;
  let marksAwarded = 0;
  if (!skipped) {
    if (q.type === "mcq") {
      given = currentOpt.value;
      if (currentOpt.value === q.correctOpt) marksAwarded = q.marks;
    } else {
      given = currentText.value;
      const len = currentText.value.trim().length;
      marksAwarded = Math.min(q.marks, Math.round((len / 220) * q.marks));
    }
  }
  answers.value = answers.value.map((a, i) => (i === qIdx.value ? { given, skipped, marksAwarded, timeSec } : a));
}

function resetQuestionUi() {
  currentText.value = "";
  currentOpt.value = null;
  hintsShown.value = 0;
  showSample.value = false;
  questionStart = Date.now();
}

function goNext() {
  if (qIdx.value + 1 < questions.value.length) {
    qIdx.value += 1;
    resetQuestionUi();
  } else {
    const firstSkipped = answers.value.findIndex((a) => a.skipped);
    if (firstSkipped >= 0) {
      qIdx.value = firstSkipped;
      resetQuestionUi();
      answers.value = answers.value.map((a, i) => (i === firstSkipped ? { ...a, skipped: false } : a));
    } else {
      finishSession();
    }
  }
}

function clearActive() {
  if (sessionId) clearInProgress(sessionId);
  sessionId = null;
  startedAt = null;
}

function persistSession() {
  const totalMarks = questions.value.reduce((s, q) => s + q.marks, 0);
  const earned = answers.value.reduce((s, a) => s + a.marksAwarded, 0);
  const accuracy = totalMarks ? Math.round((earned / totalMarks) * 100) : 0;
  const avgPerQ = answers.value.length ? Math.round(answers.value.reduce((s, a) => s + a.timeSec, 0) / answers.value.length) : 0;
  const topicLabel = wiz.value.topics.length === 0 ? "All topics" : wiz.value.topics.length === 1 ? wiz.value.topics[0] : `${wiz.value.topics.length} topics`;
  const saved = saveSession({
    level: subscription.value.level,
    subject: wiz.value.subject,
    topic: topicLabel,
    type: "Learn",
    earned,
    totalMarks,
    accuracy,
    grade: gradeFor(accuracy),
    timeSec: sessionSec.value,
    avgPerQ,
    questions: questions.value.map((q, i) => {
      const a = answers.value[i];
      const given = a.given === null ? null : q.type === "mcq" ? (q.opts ? q.opts[a.given as number] : String(a.given)) : String(a.given);
      return {
        q: q.q,
        type: q.type,
        marks: q.marks,
        marksAwarded: a.marksAwarded,
        correct: a.marksAwarded === q.marks,
        skipped: a.skipped || a.given === null,
        given,
        starAnswer: q.starAnswer,
        timeSec: a.timeSec,
      };
    }),
    metadata: { question_source: wiz.value.questionSource === "ai" ? "ai" : wiz.value.questionSource },
  });
  lastSavedId.value = saved.id;
}

function finishSession() {
  if (sessionTick) clearInterval(sessionTick);
  if (practiceTick) clearInterval(practiceTick);
  persistSession();
  clearActive();
  stage.value = "review";
}

function submitPartial() {
  if (sessionTick) clearInterval(sessionTick);
  if (practiceTick) clearInterval(practiceTick);
  answers.value = answers.value.map((a) => (a.given === null && !a.skipped ? { ...a, skipped: true } : a));
  setTimeout(() => {
    persistSession();
    clearActive();
    exitOpen.value = false;
    stage.value = "review";
  }, 0);
}

function discardSession() {
  if (sessionTick) clearInterval(sessionTick);
  if (practiceTick) clearInterval(practiceTick);
  clearActive();
  exitOpen.value = false;
  questions.value = [];
  answers.value = [];
  qIdx.value = 0;
  sessionSec.value = 0;
  stage.value = "setup";
  router.push("/learn");
}

function resumeLater() {
  exitOpen.value = false;
  router.push(defaultAuthenticatedPath(currentUser()));
}

function retryQuestion(i: number) {
  stage.value = "session";
  qIdx.value = i;
  resetQuestionUi();
}
</script>

<template>
  <SetupStage
    v-if="stage === 'setup'"
    :subjects="subscription.subjects"
    :level="subscription.level"
    :exam-board="subscription.examBoard"
    :subject="wiz.subject"
    :topics="wiz.topics"
    :topics-by-subject="subscription.topicsBySubject"
    :qtypes="wiz.qtypes"
    :difficulty="wiz.difficulty"
    :count="wiz.count"
    :practice-min="wiz.practiceMin"
    :answer-method="wiz.answerMethod"
    :question-source="wiz.questionSource"
    :learning-mode="wiz.learningMode"
    :timed="wiz.timed"
    :feedback-depth="wiz.feedbackDepth"
    :instant-explanation="wiz.instantExplanation"
    @update:subject="(v) => setWiz({ subject: v, topics: [], level: subscription.level })"
    @toggle-topic="toggleTopic"
    @set-all-topics="(all) => setWiz({ topics: all })"
    @clear-topics="setWiz({ topics: [] })"
    @toggle-qtype="toggleQType"
    @update:difficulty="(v) => setWiz({ difficulty: v })"
    @update:count="(v) => setWiz({ count: v })"
    @update:practiceMin="(v) => setWiz({ practiceMin: v })"
    @update:answerMethod="(v) => setWiz({ answerMethod: v })"
    @update:questionSource="(v) => setWiz({ questionSource: v })"
    @update:learningMode="(v) => setWiz({ learningMode: v })"
    @update:timed="(v) => setWiz({ timed: v, practiceMin: v ? wiz.practiceMin || 30 : 0 })"
    @update:feedbackDepth="(v) => setWiz({ feedbackDepth: v })"
    @update:instantExplanation="(v) => setWiz({ instantExplanation: v })"
    @start="startSession"
  />

  <SessionStage
    v-else-if="stage === 'session'"
    :subject="wiz.subject"
    :questions="questions"
    :answers="answers"
    :q-idx="qIdx"
    :current-text="currentText"
    :current-opt="currentOpt"
    :hints-shown="hintsShown"
    :show-sample="showSample"
    :session-sec="sessionSec"
    :practice-left="practiceLeft"
    :practice-min="wiz.practiceMin"
    :answer-method="wiz.answerMethod"
    :exit-open="exitOpen"
    :session-id="sessionId || ''"
    @update:current-text="(v) => (currentText = v)"
    @update:current-opt="(v) => (currentOpt = v)"
    @show-hint="hintsShown = Math.min(questions[qIdx].hints.length, hintsShown + 1)"
    @toggle-sample="showSample = !showSample"
    @skip="() => { recordAnswer(true); goNext(); }"
    @submit="() => { recordAnswer(false); goNext(); }"
    @open-exit="exitOpen = true"
    @close-exit="exitOpen = false"
    @resume-later="resumeLater"
    @submit-partial="submitPartial"
    @discard="discardSession"
  />

  <ReviewStage
    v-else-if="stage === 'review'"
    :subject="wiz.subject"
    :topics="wiz.topics"
    :questions="questions"
    :answers="answers"
    :session-sec="sessionSec"
    :last-saved-id="lastSavedId"
    @retry-question="retryQuestion"
    @train-again="stage = 'setup'"
  />

  <AppShell v-else title="Learn">
    <Loader2 class="animate-spin" />
  </AppShell>
</template>
