<script setup lang="ts">
import AppShell from "@/components/app/AppShell.vue";
import { ArrowLeft, Timer as TimerIcon, Flag } from "lucide-vue-next";
import type { ExamQ } from "./data";
import { fmtTime } from "./data";

const props = defineProps<{
  questions: ExamQ[];
  answers: string[];
  flagged: Set<number>;
  secondsLeft: number;
}>();

const emit = defineEmits<{ back: []; "goto-question": [index: number]; submit: [] }>();
</script>

<template>
  <AppShell title="Review paper">
    <div class="max-w-[900px] mx-auto space-y-3 reveal-up">
      <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <button class="text-[13px] text-ink3 inline-flex items-center gap-1.5 min-h-11" @click="emit('back')">
          <ArrowLeft :size="13" /> Back to exam
        </button>
        <p class="font-mono text-[14px] text-gold flex items-center gap-1.5">
          <TimerIcon :size="14" /> {{ fmtTime(secondsLeft) }}
        </p>
      </div>
      <div class="bg-bg-card border border-border rounded-2xl p-6">
        <p class="label-mono mb-4">Review · {{ answers.filter((a) => a.trim()).length }} of {{ questions.length }} answered</p>
        <div class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 gap-2">
          <button
            v-for="(_, i) in questions"
            :key="i"
            :class="`aspect-square rounded text-[12px] font-mono font-medium relative min-h-11 ${
              flagged.has(i) ? 'bg-pink-light text-pink border border-pink' : answers[i]?.trim() ? 'bg-mint-light text-mint' : 'bg-bg-soft text-ink4 border border-border'
            }`"
            @click="emit('goto-question', i)"
          >
            {{ i + 1 }}
            <Flag v-if="flagged.has(i)" :size="9" class="absolute top-0.5 right-0.5" />
          </button>
        </div>
      </div>
      <button class="w-full bg-gold text-white text-[15px] font-semibold h-12 rounded-md hover:opacity-90" @click="emit('submit')">
        Submit paper
      </button>
    </div>
  </AppShell>
</template>
