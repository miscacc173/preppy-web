<script setup lang="ts">
import AppShell from "@/components/app/AppShell.vue";
import { QuestionVisual } from "@/components/app/question-visual";
import { ArrowLeft, ArrowRight, ListChecks } from "lucide-vue-next";
import type { ExamQ, Paper } from "./data";
import TutorQuestionFeedback from "@/components/app/TutorQuestionFeedback.vue";

const props = defineProps<{ subject: string; paper: Paper | null; questions: ExamQ[]; answers: string[]; sessionId: string }>();
const emit = defineEmits<{ back: []; "practice-weak-topic": []; "attempt-another": [] }>();

function awardedFor(given: string, q: ExamQ) {
  const len = given.trim().length;
  return given.trim() ? Math.min(q.marks, Math.max(1, Math.round((len / 180) * q.marks))) : 0;
}
function toneFor(awarded: number, marks: number) {
  return awarded === marks ? "mint" : awarded > 0 ? "gold" : "pink";
}
</script>

<template>
  <AppShell title="Detailed feedback">
    <div class="max-w-[1000px] mx-auto space-y-5">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <button class="text-[13px] text-ink3 inline-flex items-center gap-1.5 min-h-11" @click="emit('back')">
          <ArrowLeft :size="13" /> Back to results
        </button>
        <p class="label-mono">{{ subject }} · {{ paper?.name }}</p>
      </div>
      <div class="space-y-4">
        <div v-for="(q, i) in questions" :key="i" class="bg-bg-card border border-border rounded-2xl p-6">
          <div class="flex items-start justify-between gap-3 mb-3 flex-wrap">
            <div class="min-w-0">
              <p class="label-mono mb-1">Question {{ i + 1 }} · {{ q.marks }} marks</p>
              <h3 class="text-[16px] font-semibold text-ink leading-snug">{{ q.q }}</h3>
            </div>
            <span :class="`font-mono text-[12px] px-3 py-1 rounded-full border bg-${toneFor(awardedFor(answers[i] ?? '', q), q.marks)}-light text-${toneFor(awardedFor(answers[i] ?? '', q), q.marks)} border-${toneFor(awardedFor(answers[i] ?? '', q), q.marks)}-light shrink-0`">
              {{ awardedFor(answers[i] ?? "", q) }}/{{ q.marks }}
            </span>
          </div>
          <QuestionVisual v-if="q.visual" :visual="q.visual" :figure-number="i + 1" />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
            <div class="bg-bg-soft border border-border rounded-lg p-4">
              <p class="text-[11px] font-semibold text-ink4 uppercase tracking-wider mb-2">Your answer</p>
              <p class="text-[13px] text-ink2 leading-relaxed whitespace-pre-wrap">
                <span v-if="!(answers[i] ?? '').trim()" class="italic text-ink3">No answer submitted</span>
                <template v-else>{{ answers[i] }}</template>
              </p>
            </div>
            <div class="bg-violet-light/40 border border-violet-light rounded-lg p-4">
              <p class="text-[11px] font-semibold text-violet uppercase tracking-wider mb-2">Model answer</p>
              <p class="text-[13px] text-ink leading-relaxed">{{ q.model }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
            <div class="bg-bg-soft border-l-[3px] border-l-blue border border-border rounded-lg p-4">
              <p class="text-[11px] font-semibold text-blue uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <ListChecks :size="12" /> Examiner notes
              </p>
              <p class="text-[12.5px] text-ink2 italic leading-relaxed">{{ q.examiner }}</p>
            </div>
            <div class="bg-mint-light/30 border border-mint-light rounded-lg p-4">
              <p class="text-[11px] font-semibold text-mint uppercase tracking-wider mb-2">Strengths</p>
              <ul class="space-y-1 text-[12.5px] text-ink2">
                <li v-for="s in q.strengths" :key="s">• {{ s }}</li>
              </ul>
            </div>
          </div>

          <div class="mt-3 bg-pink-light/30 border border-pink-light rounded-lg p-4">
            <p class="text-[11px] font-semibold text-pink uppercase tracking-wider mb-2">Improvement suggestion</p>
            <p class="text-[12.5px] text-ink2 leading-relaxed">{{ q.improvement }}</p>
          </div>
          <TutorQuestionFeedback session-type="exam" :session-id="sessionId" :question-key="`exam-${i}`" :question-text="q.q" :answer-text="answers[i] ?? ''" />
        </div>
      </div>

      <div class="bg-bg-card border border-border rounded-2xl p-5 flex flex-wrap gap-3 justify-between items-center">
        <p class="text-[13px] text-ink3">Use this feedback to plan your next practice.</p>
        <div class="flex gap-2 flex-wrap">
          <RouterLink to="/learn" class="inline-flex items-center gap-2 bg-bg-soft border border-border text-ink2 text-[13px] font-semibold px-5 h-11 rounded-md hover:bg-bg-soft2">
            Practice weak topic
          </RouterLink>
          <button class="inline-flex items-center gap-2 bg-gold text-white text-[13px] font-semibold px-5 h-11 rounded-md hover:opacity-90" @click="emit('attempt-another')">
            Attempt another paper <ArrowRight :size="14" />
          </button>
        </div>
      </div>
    </div>
  </AppShell>
</template>
