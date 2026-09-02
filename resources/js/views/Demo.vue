<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import { ArrowRight, ArrowLeft, Loader2 } from "lucide-vue-next";
import { QUESTION_BANK, SUBJECTS, TOPICS, type Subject } from "@/lib/questionBank";
import PillGrid from "./demo/PillGrid.vue";
import { platformRepository } from "@/data/repositories/platformRepository";
import { ApiError, storeAuth } from "@/lib/api";
import AcademicCatalogFields from "@/components/catalog/AcademicCatalogFields.vue";
import type { UiCatalog } from "@/data/models/platform";
import { academicSelectionComplete } from "@/data/academicSelection";

setPageMeta({ title: "Free Diagnostic — AiStar Prep", description: "Take the 6-minute adaptive diagnostic and see your weak topics." });

const QTYPES = ["Multiple Choice", "Short Answer", "Structured"] as const;
const DIFFICULTIES = ["Foundation", "Standard", "Challenging", "Exam-grade"] as const;

type StepKey = "academic" | "topic" | "qtype" | "difficulty" | "review" | "email" | "otp" | "quiz" | "loading" | "results";
const WIZARD_STEPS: StepKey[] = ["academic", "topic", "qtype", "difficulty", "review"];

const router = useRouter();
const step = ref<StepKey>("academic");
const catalog = ref<UiCatalog | null>(null);
const courseId = ref<number | null>(null);
const boardId = ref<number | null>(null);
const subjectIds = ref<number[]>([]);
const level = ref("");
const subject = ref<Subject | "">("");
const topic = ref("");
const qtype = ref("");
const difficulty = ref("");
const guestDemoId = ref("");
const guestDemoAccessToken = ref("");
const demoError = ref("");

const email = ref("");
const verifiedEmail = ref("");
const verificationChallengeId = ref("");
const verificationCode = ref("");
const verificationGrant = ref("");
const verificationLoading = ref(false);

const accountName = ref("");
const accountPassword = ref("");
const accountPasswordConfirmation = ref("");
const accountLoading = ref(false);
const accountError = ref("");

const qIdx = ref(0);
const answers = ref<number[]>([]);

const questions = computed(() => (subject.value ? QUESTION_BANK[subject.value] : []));
const demoCatalog = computed<UiCatalog | null>(() => {
  if (!catalog.value) return null;
  const supported = new Set<string>(SUBJECTS);
  const subjects = catalog.value.subjects.filter((item) => supported.has(item.name));
  const ids = new Set(subjects.map((item) => item.id));
  return { ...catalog.value, subjects, courses: catalog.value.courses.map((course) => ({ ...course, subjectIds: course.subjectIds.filter((id) => ids.has(id)) })) };
});
const selectedCourse = computed(() => catalog.value?.courses.find((item) => item.id === courseId.value) ?? null);
const selectedBoard = computed(() => catalog.value?.examBoards.find((item) => item.id === boardId.value) ?? null);

watch(subjectIds, (ids) => {
  const selected = catalog.value?.subjects.find((item) => item.id === ids[0]);
  subject.value = (selected?.name ?? "") as Subject | "";
  topic.value = "";
}, { deep: true });

onMounted(async () => {
  try { catalog.value = await platformRepository.catalog(); }
  catch (reason) { demoError.value = apiMessage(reason, "Academic options could not be loaded."); }
});
const total = computed(() => questions.value.length || 10);

const wizardIndex = computed(() => WIZARD_STEPS.indexOf(step.value));
const isWizard = computed(() => wizardIndex.value !== -1);
const stepNumber = computed(() => (isWizard.value ? wizardIndex.value + 1 : WIZARD_STEPS.length));
const progress = computed(() => (isWizard.value ? (stepNumber.value / WIZARD_STEPS.length) * 100 : 100));

function goTo(s: StepKey) {
  demoError.value = "";
  step.value = s;
}

function apiMessage(reason: unknown, fallback: string) {
  if (reason instanceof ApiError) {
    const firstValidationMessage = Object.values(reason.errors)[0]?.[0];
    return firstValidationMessage || reason.message;
  }
  return reason instanceof Error ? reason.message : fallback;
}

async function requestEmailVerification() {
  verificationLoading.value = true;
  demoError.value = "";
  try {
    const challenge = await platformRepository.requestGuestDemoEmailVerification(email.value);
    verificationChallengeId.value = challenge.id;
    verifiedEmail.value = challenge.email;
    verificationCode.value = "";

    if (challenge.development_bypass && challenge.verification_grant) {
      verificationGrant.value = challenge.verification_grant;
      await startDiagnostic();
      return;
    }

    step.value = "otp";
  } catch (reason) {
    demoError.value = apiMessage(reason, "Unable to verify the email or start the diagnostic.");
  } finally {
    verificationLoading.value = false;
  }
}

async function resendEmailVerification() {
  await requestEmailVerification();
}

async function verifyEmailAndStartDiagnostic() {
  verificationLoading.value = true;
  demoError.value = "";
  try {
    const verified = await platformRepository.verifyGuestDemoEmail(verificationChallengeId.value, verificationCode.value);
    verificationGrant.value = verified.verification_grant;
    verifiedEmail.value = verified.email;
    await startDiagnostic();
  } catch (reason) {
    demoError.value = apiMessage(reason, "Unable to verify your email.");
  } finally {
    verificationLoading.value = false;
  }
}

function submitQ(choice: number) {
  const nextAnswers = [...answers.value, choice];
  answers.value = nextAnswers;
  if (guestDemoId.value && guestDemoAccessToken.value) {
    void platformRepository.saveGuestDemoProgress(guestDemoId.value, guestDemoAccessToken.value, {
      level: level.value,
      subject: subject.value,
      topic: topic.value,
      question_type: qtype.value,
      difficulty: difficulty.value,
      questions_attempted: nextAnswers.length,
      answers: nextAnswers,
      completed: qIdx.value + 1 >= total.value,
      score: nextAnswers.reduce((acc, answer, index) => acc + (answer === questions.value[index]?.a ? 1 : 0), 0),
    }).catch(() => undefined);
  }
  if (qIdx.value + 1 < total.value) {
    qIdx.value += 1;
  } else {
    step.value = "loading";
    setTimeout(() => (step.value = "results"), 1800);
  }
}

async function startDiagnostic() {
  qIdx.value = 0;
  answers.value = [];
  demoError.value = "";

  const course = selectedCourse.value;
  const response = await platformRepository.startGuestDemo({
    verification_grant: verificationGrant.value,
    course_id: course!.id,
    exam_board_id: boardId.value!,
    subject_ids: subjectIds.value,
    metadata: {
      level: level.value,
      subject: subject.value,
      topic: topic.value,
      question_type: qtype.value,
      difficulty: difficulty.value,
    },
  });

  guestDemoId.value = response.data.id;
  guestDemoAccessToken.value = response.access_token;
  goTo("quiz");
}

async function createAccountFromDemo() {
  if (!guestDemoId.value || !guestDemoAccessToken.value) return;

  accountLoading.value = true;
  accountError.value = "";
  try {
    const response = await platformRepository.convertGuestDemo(guestDemoId.value, guestDemoAccessToken.value, {
      name: accountName.value,
      password: accountPassword.value,
      password_confirmation: accountPasswordConfirmation.value,
    });
    storeAuth(response);
    await router.push({ path: "/pricing", query: { from: "guest-diagnostic" } });
  } catch (reason) {
    accountError.value = apiMessage(reason, "Unable to create your account.");
  } finally {
    accountLoading.value = false;
  }
}

const score = computed(() => answers.value.reduce((acc, a, i) => acc + (a === questions.value[i]?.a ? 1 : 0), 0));
const pct = computed(() => Math.round((score.value / total.value) * 100));

interface StepConfig {
  title: string;
  sub: string;
  canContinue: boolean;
  onContinue: () => void;
  back?: StepKey;
}

const stepConfig = computed<Record<string, StepConfig | null>>(() => ({
  academic: { title: "Choose your course, board and subject", sub: "Boards and subjects are limited to the selected course.", canContinue: academicSelectionComplete(demoCatalog.value, courseId.value, boardId.value, subjectIds.value), onContinue: () => { level.value = selectedCourse.value?.level || selectedCourse.value?.rawLevel || ""; goTo("topic"); } },
  topic: { title: "Pick a topic", sub: "Choose where you feel least confident.", canContinue: !!topic.value, onContinue: () => goTo("qtype"), back: "academic" },
  qtype: { title: "Question type", sub: "Diagnostic uses multiple choice for speed.", canContinue: !!qtype.value, onContinue: () => goTo("difficulty"), back: "topic" },
  difficulty: { title: "Difficulty level", sub: "We'll calibrate question depth to match.", canContinue: !!difficulty.value, onContinue: () => goTo("review"), back: "qtype" },
  review: {
    title: "Review your session",
    sub: "Confirm your settings, then verify your email to begin.",
    canContinue: true,
    onContinue: () => goTo("email"),
    back: "difficulty",
  },
  email: null,
  otp: null,
  quiz: null,
  loading: null,
  results: null,
}));

const reviewRows = computed(() => [
  { l: "Academic level", v: level.value },
  { l: "Course", v: selectedCourse.value?.name ?? "" },
  { l: "Exam board", v: selectedBoard.value?.name ?? "" },
  { l: "Subject", v: subject.value },
  { l: "Topic", v: topic.value },
  { l: "Question type", v: qtype.value },
  { l: "Difficulty", v: difficulty.value },
  { l: "Questions", v: `${total.value}` },
]);

function onTopBarClick() {
  if (step.value === "email") return goTo("review");
  if (step.value === "otp") return goTo("email");
  const cfg = stepConfig.value[step.value];
  if (cfg?.back) goTo(cfg.back);
  else router.push("/");
}
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <main class="flex-1 max-w-[760px] w-full mx-auto px-5 py-8">
      <!-- Top bar: Cancel + step -->
      <div class="flex items-center justify-between mb-3">
        <button class="inline-flex items-center gap-1.5 text-[13px] text-ink2 hover:text-ink" @click="onTopBarClick">
          <ArrowLeft :size="14" /> {{ step === "email" || step === "otp" || stepConfig[step]?.back ? "Back" : "Cancel" }}
        </button>
        <p v-if="isWizard" class="font-mono text-[11px] text-ink3 tracking-[0.12em] uppercase">Step {{ stepNumber }} of {{ WIZARD_STEPS.length }}</p>
      </div>

      <!-- Progress -->
      <div v-if="isWizard" class="h-1 bg-bg-soft2 rounded-full overflow-hidden mb-6">
        <div class="h-full bg-violet rounded-full transition-all duration-500" :style="{ width: `${progress}%` }" />
      </div>

      <!-- Card -->
      <p v-if="demoError" class="mb-4 rounded-lg bg-gold-light px-4 py-3 text-[12.5px] text-ink2">{{ demoError }}</p>
      <div v-if="isWizard" :key="step" class="bg-bg-card border border-border rounded-2xl p-7 reveal-up">
        <h2 class="text-[22px] font-bold text-ink tracking-[-0.02em] mb-1.5">{{ stepConfig[step]!.title }}</h2>
        <p class="text-[13.5px] text-ink3 mb-6 font-light">{{ stepConfig[step]!.sub }}</p>

        <AcademicCatalogFields v-if="step === 'academic'" :catalog="demoCatalog" :single-subject="true" v-model:course-id="courseId" v-model:board-id="boardId" v-model:subject-ids="subjectIds" />
        <PillGrid v-if="step === 'topic' && subject" v-model="topic" :items="TOPICS[subject]" />
        <PillGrid v-if="step === 'qtype'" v-model="qtype" :items="[...QTYPES]" />
        <PillGrid v-if="step === 'difficulty'" v-model="difficulty" :items="[...DIFFICULTIES]" />

        <div v-if="step === 'review'" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <div v-for="r in reviewRows" :key="r.l" class="border border-border rounded-lg px-4 py-3 bg-bg-soft">
            <p class="label-mono">{{ r.l }}</p>
            <p class="text-[14px] text-ink font-medium mt-0.5">{{ r.v || "—" }}</p>
          </div>
        </div>

        <button
          class="mt-7 w-full inline-flex items-center justify-center gap-2 bg-violet text-white text-[14px] font-semibold py-3.5 rounded-md hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition"
          :disabled="!stepConfig[step]!.canContinue"
          @click="stepConfig[step]!.onContinue"
        >
          {{ step === "review" ? "Verify email to continue" : "Continue" }} <ArrowRight :size="15" />
        </button>
      </div>

      <div v-if="step === 'email'" class="bg-bg-card border border-border rounded-2xl p-7 reveal-up">
        <p class="label-mono mb-3">Email verification</p>
        <h2 class="text-[22px] font-bold text-ink tracking-[-0.02em] mb-1.5">Verify your email to start</h2>
        <p class="text-[13.5px] text-ink3 mb-6 font-light">We'll send a 6-digit verification code. One guest diagnostic session is available per verified email, which can also be used to create your account afterward.</p>
        <label class="block text-[12px] font-medium text-ink2 mb-1.5">Email address</label>
        <input
          v-model="email"
          type="email"
          autocomplete="email"
          required
          placeholder="you@example.com"
          class="w-full bg-bg-card border border-border rounded-md px-3.5 py-3 text-[14px] text-ink outline-none focus:border-violet"
          @keyup.enter="requestEmailVerification"
        />
        <button
          class="mt-5 w-full inline-flex items-center justify-center gap-2 bg-violet text-white text-[14px] font-semibold py-3.5 rounded-md hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition"
          :disabled="verificationLoading || !email"
          @click="requestEmailVerification"
        >
          <Loader2 v-if="verificationLoading" :size="15" class="animate-spin" />
          {{ verificationLoading ? "Sending code…" : "Send verification code" }}
          <ArrowRight v-if="!verificationLoading" :size="15" />
        </button>
      </div>

      <div v-if="step === 'otp'" class="bg-bg-card border border-border rounded-2xl p-7 reveal-up">
        <p class="label-mono mb-3">Email verification</p>
        <h2 class="text-[22px] font-bold text-ink tracking-[-0.02em] mb-1.5">Enter your verification code</h2>
        <p class="text-[13.5px] text-ink3 mb-6 font-light">We sent a 6-digit code to <span class="font-medium text-ink2">{{ verifiedEmail }}</span>.</p>
        <label class="block text-[12px] font-medium text-ink2 mb-1.5">Verification code</label>
        <input
          v-model="verificationCode"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          placeholder="000000"
          class="w-full bg-bg-card border border-border rounded-md px-3.5 py-3 text-[18px] font-mono tracking-[0.22em] text-center text-ink outline-none focus:border-violet"
          @input="verificationCode = verificationCode.replace(/\D/g, '').slice(0, 6)"
          @keyup.enter="verificationCode.length === 6 && verifyEmailAndStartDiagnostic()"
        />
        <button
          class="mt-5 w-full inline-flex items-center justify-center gap-2 bg-violet text-white text-[14px] font-semibold py-3.5 rounded-md hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition"
          :disabled="verificationLoading || verificationCode.length !== 6"
          @click="verifyEmailAndStartDiagnostic"
        >
          <Loader2 v-if="verificationLoading" :size="15" class="animate-spin" />
          {{ verificationLoading ? "Verifying…" : "Verify & start diagnostic" }}
          <ArrowRight v-if="!verificationLoading" :size="15" />
        </button>
        <button class="mt-3 w-full text-[12.5px] text-ink3 hover:text-violet" :disabled="verificationLoading" @click="resendEmailVerification">Resend code</button>
      </div>

      <template v-if="step === 'quiz' && questions[qIdx]">
        <div class="h-1 bg-bg-soft2 rounded-full overflow-hidden mb-6">
          <div class="h-full bg-violet rounded-full transition-all duration-500" :style="{ width: `${((qIdx + 1) / total) * 100}%` }" />
        </div>
        <div class="bg-bg-card border border-border rounded-2xl p-7 reveal-up">
          <p class="label-mono mb-3">Question {{ qIdx + 1 }} of {{ total }} · {{ subject }} · {{ topic }}</p>
          <p class="text-[16px] text-ink leading-relaxed mb-6">{{ questions[qIdx].q }}</p>
          <div class="flex flex-col gap-2.5">
            <button
              v-for="(opt, i) in questions[qIdx].opts"
              :key="i"
              class="text-left bg-bg-card border border-border hover:border-violet hover:bg-violet-light rounded-lg px-4 py-3.5 text-[14px] text-ink2 transition"
              @click="submitQ(i)"
            >
              <span class="font-mono text-[11px] text-ink4 mr-3">{{ String.fromCharCode(65 + i) }}</span>
              {{ opt }}
            </button>
          </div>
        </div>
      </template>

      <div v-if="step === 'loading'" class="bg-bg-card border border-border rounded-2xl p-7 reveal-up">
        <h2 class="text-[22px] font-bold text-ink mb-1.5">Preparing your diagnostic result…</h2>
        <p class="text-[13.5px] text-ink3 mb-6 font-light">Calculating your score from the answers completed in this demo.</p>
        <div class="flex flex-col items-center py-10 gap-4 text-ink3">
          <Loader2 :size="32" class="animate-spin text-violet" />
          <p class="text-sm">Saving your diagnostic progress</p>
        </div>
      </div>

      <div v-if="step === 'results'" class="space-y-5 reveal-up">
        <div class="bg-bg-card border border-border rounded-2xl p-7 shadow-soft text-center">
          <p class="label-mono mb-4">Demo results · {{ subject }}</p>
          <div class="flex items-baseline justify-center gap-3 mb-1">
            <span class="font-mono text-[64px] font-medium text-ink leading-none tracking-[-0.03em]">{{ pct }}</span>
            <span class="font-mono text-xl text-ink3">%</span>
          </div>
          <p class="text-sm text-ink3">{{ score }} of {{ total }} correct · {{ level }} · {{ subject }} · {{ topic }}</p>
        </div>

        <div class="bg-bg-soft border border-border border-l-[3px] border-l-violet rounded-r-xl p-5">
          <p class="text-[10px] font-semibold text-violet uppercase tracking-[0.07em] mb-2">Diagnostic summary</p>
          <p class="text-[14px] text-ink2 leading-relaxed">
            This result reflects only the questions answered in this free diagnostic. Create your Student account to save it, then activate a plan matched to your selected course and subject.
          </p>
        </div>

        <div class="bg-violet rounded-2xl p-7">
          <div class="text-center mb-5">
            <h3 class="text-[22px] font-bold text-white tracking-[-0.02em] mb-2">Save your diagnostic and activate a plan</h3>
            <p class="text-sm text-white/75 font-light">Create your Student account using the verified email, then choose an eligible plan.</p>
          </div>
          <div class="max-w-[460px] mx-auto space-y-3">
            <input v-model="accountName" required placeholder="Your name" class="w-full bg-white border border-white/30 rounded-md px-3.5 py-3 text-[14px] text-ink outline-none" />
            <div class="bg-white/10 border border-white/20 rounded-md px-3.5 py-3 text-[13px] text-white/85">{{ verifiedEmail }}</div>
            <input v-model="accountPassword" type="password" required minlength="8" placeholder="Password (minimum 8 characters)" class="w-full bg-white border border-white/30 rounded-md px-3.5 py-3 text-[14px] text-ink outline-none" />
            <input v-model="accountPasswordConfirmation" type="password" required minlength="8" placeholder="Confirm password" class="w-full bg-white border border-white/30 rounded-md px-3.5 py-3 text-[14px] text-ink outline-none" />
            <p v-if="accountError" class="rounded-md bg-white/10 px-3 py-2 text-[12.5px] text-white">{{ accountError }}</p>
            <button
              class="w-full inline-flex items-center justify-center gap-2 bg-white text-violet text-[14px] font-semibold px-7 py-3 rounded-lg hover:bg-bg-soft disabled:opacity-60 transition"
              :disabled="accountLoading || !accountName || accountPassword.length < 8 || accountPassword !== accountPasswordConfirmation"
              @click="createAccountFromDemo"
            >
              <Loader2 v-if="accountLoading" :size="15" class="animate-spin" />
              {{ accountLoading ? "Creating account…" : "Create account & choose plan" }}
              <ArrowRight v-if="!accountLoading" :size="16" />
            </button>
          </div>
        </div>

        <div class="text-center">
          <RouterLink to="/login" class="text-[13px] text-ink3 hover:text-ink underline">Already have an account? Sign in</RouterLink>
        </div>
      </div>
    </main>
  </div>
</template>
