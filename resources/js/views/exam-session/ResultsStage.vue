<script setup lang="ts">
import { computed } from "vue";
import AppShell from "@/components/app/AppShell.vue";
import { Ring } from "@/components/app/visuals";
import { GradeBadge } from "@/components/ui/grade-badge";
import { ArrowRight } from "lucide-vue-next";
import type { ExamQ, Paper } from "./data";
import { fmtTime } from "./data";

const props = defineProps<{
  subject: string;
  paper: Paper | null;
  questions: ExamQ[];
  answers: string[];
  flagged: Set<number>;
  secondsLeft: number;
  earned: number;
  totalMarks: number;
  accuracy: number;
  grade: string;
}>();

const emit = defineEmits<{ "view-feedback": []; "attempt-another": [] }>();

const answered = computed(() => props.answers.filter((a) => a.trim()).length);
const usedSec = computed(() => (props.paper?.minutes ?? 0) * 60 - props.secondsLeft);
</script>

<template>
  <AppShell title="Exam complete">
    <div class="max-w-[1000px] mx-auto space-y-5">
      <div class="bg-bg-card border border-border rounded-2xl p-6 lg:p-7 grid grid-cols-1 md:grid-cols-[auto_1fr] gap-7 items-center reveal-up">
        <Ring :value="accuracy" :size="170" color="var(--gold)" label="Accuracy" />
        <div>
          <p class="label-mono mb-2">{{ subject }} · {{ paper?.name }}</p>
          <h2 class="text-[24px] lg:text-[28px] font-bold text-ink tracking-[-0.025em] leading-tight flex items-center gap-3 flex-wrap">
            Predicted grade: <GradeBadge :grade="grade" size="lg" />
          </h2>
          <p class="text-[14px] text-ink3 mt-1">Pacing strong early, slowed on calculations.</p>
          <div class="grid grid-cols-3 gap-3 mt-5">
            <div v-for="s in [{ l: 'Score', v: `${earned}/${totalMarks}` }, { l: 'Answered', v: `${answered}/${questions.length}` }, { l: 'Time used', v: fmtTime(usedSec) }]" :key="s.l" class="bg-bg-soft border border-border rounded-lg p-3">
              <p class="label-mono">{{ s.l }}</p>
              <p class="font-mono text-[20px] font-medium text-ink mt-1">{{ s.v }}</p>
            </div>
          </div>
        </div>
      </div>
      <div class="flex flex-wrap gap-3">
        <button class="inline-flex items-center gap-2 bg-gold text-white text-[14px] font-semibold px-5 h-12 rounded-md hover:opacity-90" @click="emit('view-feedback')">
          View detailed feedback <ArrowRight :size="14" />
        </button>
        <RouterLink to="/results" class="inline-flex items-center gap-2 bg-bg-card border border-border text-ink2 text-[14px] font-semibold px-5 h-12 rounded-md hover:bg-bg-soft">
          Full results
        </RouterLink>
        <button class="bg-bg-soft border border-border text-ink2 text-[14px] font-semibold px-5 h-12 rounded-md hover:bg-bg-soft2" @click="emit('attempt-another')">
          Attempt another paper
        </button>
      </div>
    </div>
  </AppShell>
</template>
