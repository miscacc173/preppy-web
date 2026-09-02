<script setup lang="ts">
import AppShell from "@/components/app/AppShell.vue";
import { QuestionVisual } from "@/components/app/question-visual";
import HandwritingPad from "@/components/app/HandwritingPad.vue";
import { ArrowRight, ArrowLeft, Flag, Timer as TimerIcon, FileText } from "lucide-vue-next";
import type { AnswerMethod } from "@/lib/wizardStore";
import type { ExamQ } from "./data";
import { fmtTime } from "./data";
import TutorQuestionFeedback from "@/components/app/TutorQuestionFeedback.vue";

const props = defineProps<{
  questions: ExamQ[];
  qIdx: number;
  answers: string[];
  flagged: Set<number>;
  secondsLeft: number;
  answerMethod: AnswerMethod;
  sessionId: string;
}>();

const emit = defineEmits<{
  "update:qIdx": [number];
  "update:answer": [text: string];
  "toggle-flag": [];
  review: [];
}>();

defineExpose({});
</script>

<template>
  <AppShell title="Exam in progress">
    <div class="max-w-[900px] mx-auto">
      <div class="sticky top-14 lg:top-16 z-20 -mx-4 lg:-mx-8 px-4 lg:px-8 py-3 mb-5 bg-bg/95 backdrop-blur border-b border-border">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-2 font-mono text-[18px] font-semibold text-gold">
            <TimerIcon :size="18" /> {{ fmtTime(secondsLeft) }}
          </div>
          <div class="flex-1 min-w-[140px] mx-3">
            <p class="text-[11px] text-ink3 font-mono mb-1">Question {{ qIdx + 1 }} of {{ questions.length }}</p>
            <div class="h-1.5 bg-bg-soft2 rounded-full overflow-hidden">
              <div class="h-full bg-gold rounded-full transition-all" :style="{ width: `${((qIdx + 1) / questions.length) * 100}%` }" />
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              :class="`inline-flex items-center gap-1.5 text-[12px] font-medium rounded-md px-3 h-10 border ${
                flagged.has(qIdx) ? 'bg-pink-light border-pink text-pink' : 'bg-bg-soft border-border text-ink2'
              }`"
              @click="emit('toggle-flag')"
            >
              <Flag :size="12" /> Flag
            </button>
            <button class="inline-flex items-center gap-1.5 text-[12px] font-medium rounded-md px-3 h-10 bg-bg-soft border border-border text-ink2 hover:bg-bg-soft2" @click="emit('review')">
              <FileText :size="12" /> Review
            </button>
          </div>
        </div>
      </div>

      <div :key="qIdx" class="bg-bg-card border border-border rounded-2xl p-6 lg:p-7 reveal-up">
        <p class="label-mono mb-3">Question {{ qIdx + 1 }}</p>
        <QuestionVisual v-if="questions[qIdx].visual" :visual="questions[qIdx].visual!" :figure-number="qIdx + 1" />
        <h2 class="text-[18px] lg:text-[20px] font-semibold text-ink leading-[1.4] mb-5">{{ questions[qIdx].q }}</h2>

        <HandwritingPad
          v-if="answerMethod === 'handwritten'"
          :value="answers[qIdx] ?? ''"
          @update:value="emit('update:answer', $event)"
          :min-height="240"
          accent="gold"
        />
        <textarea
          v-else
          :value="answers[qIdx] ?? ''"
          rows="9"
          placeholder="Write your answer…"
          autocapitalize="sentences"
          class="w-full bg-bg-soft border border-border rounded-lg p-4 text-[15px] text-ink outline-none focus:border-gold resize-y min-h-[200px]"
          @input="emit('update:answer', ($event.target as HTMLTextAreaElement).value)"
        />

        <TutorQuestionFeedback
          :key="`${sessionId}-${qIdx}`"
          session-type="exam"
          :session-id="sessionId"
          :question-key="`result-${qIdx}`"
          :question-text="questions[qIdx].q"
          :answer-text="questions[qIdx].model"
        />

        <div class="flex items-center justify-between mt-4 flex-wrap gap-3">
          <button
            :disabled="qIdx === 0"
            class="inline-flex items-center gap-1.5 text-[13px] bg-bg-soft border border-border rounded-md px-4 h-11 text-ink2 disabled:opacity-40"
            @click="emit('update:qIdx', Math.max(0, qIdx - 1))"
          >
            <ArrowLeft :size="13" /> Previous
          </button>
          <button
            v-if="qIdx < questions.length - 1"
            class="inline-flex items-center gap-1.5 text-[14px] bg-gold text-white rounded-md px-5 h-12 font-semibold hover:opacity-90"
            @click="emit('update:qIdx', qIdx + 1)"
          >
            Next <ArrowRight :size="14" />
          </button>
          <button v-else class="inline-flex items-center gap-1.5 text-[14px] bg-gold text-white rounded-md px-5 h-12 font-semibold hover:opacity-90" @click="emit('review')">
            Review & submit
          </button>
        </div>
      </div>
    </div>
  </AppShell>
</template>
