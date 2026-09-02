<script setup lang="ts">
import { computed } from "vue";
import AppShell from "@/components/app/AppShell.vue";
import { QuestionVisual } from "@/components/app/question-visual";
import HandwritingPad from "@/components/app/HandwritingPad.vue";
import {
  ArrowRight, Lightbulb, Clock, SkipForward, Target, DoorOpen, BookOpen,
} from "lucide-vue-next";
import type { AnswerMethod } from "@/lib/wizardStore";
import type { AnswerRecord, Question } from "./data";
import { fmtTime } from "./data";
import Metric from "./Metric.vue";
import ExitModal from "./ExitModal.vue";
import TutorQuestionFeedback from "@/components/app/TutorQuestionFeedback.vue";

const props = defineProps<{
  subject: string;
  questions: Question[];
  answers: AnswerRecord[];
  qIdx: number;
  currentText: string;
  currentOpt: number | null;
  hintsShown: number;
  showSample: boolean;
  sessionSec: number;
  practiceLeft: number;
  practiceMin: number;
  answerMethod: AnswerMethod;
  exitOpen: boolean;
  sessionId: string;
}>();

const emit = defineEmits<{
  "update:currentText": [string];
  "update:currentOpt": [number];
  "show-hint": [];
  "toggle-sample": [];
  skip: [];
  submit: [];
  "open-exit": [];
  "close-exit": [];
  "resume-later": [];
  "submit-partial": [];
  discard: [];
}>();

const q = computed(() => props.questions[props.qIdx]);
const answeredCount = computed(() => props.answers.filter((a) => a.given !== null || a.skipped).length);
const avgTime = computed(() => (answeredCount.value ? Math.round(props.answers.reduce((s, a) => s + a.timeSec, 0) / answeredCount.value) : 0));
const pace = computed(() => (avgTime.value < 60 ? "Fast" : avgTime.value < 120 ? "On pace" : "Slow"));
const paceColor = computed(() => (pace.value === "Slow" ? "text-pink" : pace.value === "Fast" ? "text-blue" : "text-mint"));
const canSubmit = computed(() => (q.value.type === "mcq" ? props.currentOpt !== null : !!props.currentText.trim()));
const submitLabel = computed(() =>
  props.qIdx + 1 === props.questions.length && !props.answers.some((a) => a.skipped) ? "Submit session" : "Submit & next",
);
</script>

<template>
  <AppShell :title="`${subject} · Learn`">
    <div class="max-w-[1000px] mx-auto">
      <div class="mb-5 bg-bg-card border border-border rounded-xl px-4 py-3 flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[160px]">
          <div class="flex justify-between text-[11px] text-ink3 font-mono mb-1.5">
            <span>Question {{ qIdx + 1 }} of {{ questions.length }}</span>
            <span>{{ Math.round(((qIdx + 1) / questions.length) * 100) }}%</span>
          </div>
          <div class="h-1.5 bg-bg-soft2 rounded-full overflow-hidden">
            <div class="h-full bg-violet rounded-full transition-all" :style="{ width: `${((qIdx + 1) / questions.length) * 100}%` }" />
          </div>
        </div>
        <Metric label="Session" :value="fmtTime(sessionSec)"><template #icon><Clock :size="13" /></template></Metric>
        <Metric label="Avg/Q" :value="`${avgTime}s`"><template #icon><Target :size="13" /></template></Metric>
        <Metric label="Pace" :value="pace" :value-class="paceColor" />
        <Metric v-if="practiceMin > 0" label="Timer" :value="fmtTime(practiceLeft)" value-class="text-gold"><template #icon><Clock :size="13" /></template></Metric>
        <button
          class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-ink2 bg-bg-soft border border-border rounded-md px-3 h-9 hover:bg-bg-soft2 hover:text-ink"
          aria-label="Exit session"
          @click="emit('open-exit')"
        >
          <DoorOpen :size="13" /> Exit
        </button>
      </div>

      <ExitModal
        v-if="exitOpen"
        :answered="answers.filter((a) => a.given !== null && !a.skipped).length"
        :total="questions.length"
        @close="emit('close-exit')"
        @resume-later="emit('resume-later')"
        @submit="emit('submit-partial')"
        @discard="emit('discard')"
      />

      <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-5 reveal-up">
        <div class="bg-bg-card border border-border rounded-2xl p-6 lg:p-7">
          <p class="label-mono mb-3">Question {{ qIdx + 1 }} · {{ q.marks }} marks · {{ q.type === "mcq" ? "MCQ" : "Short answer" }}</p>
          <QuestionVisual v-if="q.visual" :visual="q.visual" :figure-number="qIdx + 1" />
          <h2 class="text-[18px] lg:text-[20px] font-bold text-ink leading-[1.4] mb-5">{{ q.q }}</h2>

          <div v-if="q.type === 'mcq'" class="space-y-2">
            <button
              v-for="(opt, i) in q.opts"
              :key="i"
              :class="`w-full text-left p-4 rounded-lg border-2 transition min-h-[52px] flex items-center gap-3 ${
                currentOpt === i ? 'bg-violet-light border-violet' : 'bg-bg-soft border-border hover:border-violet'
              }`"
              @click="emit('update:currentOpt', i)"
            >
              <span :class="`w-7 h-7 rounded-full grid place-items-center text-[12px] font-semibold shrink-0 ${currentOpt === i ? 'bg-violet text-white' : 'bg-bg-card border border-border text-ink3'}`">
                {{ String.fromCharCode(65 + i) }}
              </span>
              <span class="text-[14px] text-ink">{{ opt }}</span>
            </button>
          </div>
          <template v-else>
            <label class="block text-[12px] font-medium text-ink2 mb-2">
              Your answer {{ answerMethod === "handwritten" ? "(handwritten)" : "" }}
            </label>
            <HandwritingPad v-if="answerMethod === 'handwritten'" :value="currentText" @update:value="emit('update:currentText', $event)" :min-height="220" accent="violet" />
            <textarea
              v-else
              :value="currentText"
              rows="8"
              placeholder="Type your answer…"
              class="w-full bg-bg-soft border border-border rounded-lg p-4 text-[15px] text-ink outline-none focus:border-violet resize-none min-h-[180px]"
              @input="emit('update:currentText', ($event.target as HTMLTextAreaElement).value)"
            />
          </template>

          <TutorQuestionFeedback
            :key="`${sessionId}-${qIdx}`"
            session-type="learn"
            :session-id="sessionId"
            :question-key="`result-${qIdx}`"
            :question-text="q.q"
            :answer-text="q.starAnswer"
          />

          <div class="flex items-center justify-between mt-5 flex-wrap gap-3">
            <button class="inline-flex items-center gap-2 text-[13px] text-ink2 bg-bg-soft border border-border rounded-md px-4 h-11 hover:bg-bg-soft2" @click="emit('skip')">
              <SkipForward :size="14" /> Skip
            </button>
            <button
              :disabled="!canSubmit"
              class="inline-flex items-center gap-2 bg-violet text-white text-[14px] font-semibold px-6 h-12 rounded-lg hover:opacity-90 disabled:opacity-40"
              @click="emit('submit')"
            >
              {{ submitLabel }} <ArrowRight :size="14" />
            </button>
          </div>
        </div>

        <aside class="space-y-4">
          <div class="bg-bg-card border border-border rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
              <p class="label-mono">Progressive hints</p>
              <span class="text-[11px] text-ink3">{{ hintsShown }}/{{ q.hints.length }}</span>
            </div>
            <div class="space-y-2 mb-3">
              <div v-for="(h, i) in q.hints.slice(0, hintsShown)" :key="i" class="flex gap-2 text-[13px] text-ink2 p-2.5 rounded-md bg-gold-light/40 border border-gold-light">
                <Lightbulb :size="14" class="text-gold mt-0.5 shrink-0" />
                <span><span class="font-semibold text-gold">Hint {{ i + 1 }}.</span> {{ h }}</span>
              </div>
            </div>
            <button
              :disabled="hintsShown >= q.hints.length"
              class="w-full inline-flex items-center justify-center gap-2 text-[13px] font-semibold text-gold bg-gold-light border border-gold-light rounded-md h-11 hover:bg-gold-light/70 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="emit('show-hint')"
            >
              <Lightbulb :size="14" /> {{ hintsShown === 0 ? "Get hint" : `Next hint (${hintsShown + 1}/${q.hints.length})` }}
            </button>
            <p class="text-[11px] text-ink3 mt-2 text-center">Hints guide your thinking — they won't reveal the answer.</p>
          </div>

          <div class="bg-bg-card border border-border rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
              <p class="label-mono">Sample answer</p>
              <span v-if="showSample" class="text-[10px] font-semibold text-violet bg-violet-light rounded-full px-2 py-0.5">Revealed</span>
            </div>
            <div v-if="showSample" class="text-[13px] text-ink2 leading-relaxed bg-violet-light/40 border border-violet-light rounded-md p-3 mb-3">
              {{ q.starAnswer }}
            </div>
            <p v-else class="text-[12px] text-ink3 mb-3">A high-quality examiner-level response.</p>
            <button class="w-full inline-flex items-center justify-center gap-2 text-[13px] font-semibold text-violet bg-violet-light border border-violet-light rounded-md h-11 hover:bg-violet-light/70" @click="emit('toggle-sample')">
              <BookOpen :size="14" /> {{ showSample ? "Hide sample answer" : "View sample answer" }}
            </button>
          </div>

          <div class="bg-bg-soft border border-border rounded-xl p-5">
            <p class="label-mono mb-2">Skipped queue</p>
            <p class="text-[12px] text-ink3">{{ answers.filter((a) => a.skipped).length }} skipped · revisit after final question.</p>
          </div>
        </aside>
      </div>
    </div>
  </AppShell>
</template>
