<script setup lang="ts">
import { computed } from "vue";
import AppShell from "@/components/app/AppShell.vue";
import { ArrowRight, Check, Clock, Sparkles, TrendingUp, Keyboard, PenLine, Zap, Timer as TimerIcon } from "lucide-vue-next";
import { LEARN_QTYPE_LABELS, type LearnDifficulty, type LearnQTypeLabel, type AnswerMethod, type QuestionSource, type LearningMode, type FeedbackDepth } from "@/lib/wizardStore";
import { DIFFICULTIES, COUNTS, PRACTICE_TIMERS } from "./data";
import SetupCard from "./SetupCard.vue";
import PillGrid from "./PillGrid.vue";
import ToggleSwitch from "./ToggleSwitch.vue";

const props = defineProps<{
  subjects: string[];
  level: string;
  examBoard: string;
  subject: string;
  topics: string[];
  topicsBySubject: Record<string, string[]>;
  qtypes: LearnQTypeLabel[];
  difficulty: LearnDifficulty;
  count: number;
  practiceMin: number;
  answerMethod: AnswerMethod;
  questionSource: QuestionSource;
  learningMode: LearningMode;
  timed: boolean;
  feedbackDepth: FeedbackDepth;
  instantExplanation: boolean;
}>();

const emit = defineEmits<{
  "update:subject": [string];
  "toggle-topic": [string];
  "set-all-topics": [string[]];
  "clear-topics": [];
  "toggle-qtype": [LearnQTypeLabel];
  "update:difficulty": [LearnDifficulty];
  "update:count": [number];
  "update:practiceMin": [number];
  "update:answerMethod": [AnswerMethod];
  "update:questionSource": [QuestionSource];
  "update:learningMode": [LearningMode];
  "update:timed": [boolean];
  "update:feedbackDepth": [FeedbackDepth];
  "update:instantExplanation": [boolean];
  start: [];
}>();

const allTopics = computed(() => props.topicsBySubject[props.subject] ?? []);
const allSelected = computed(() => allTopics.value.length > 0 && allTopics.value.every((t) => props.topics.includes(t)));
const topicLabel = computed(() => (props.topics.length === 0 ? "All topics" : props.topics.length === 1 ? props.topics[0] : `${props.topics.length} topics`));
const avgMinPerQ = computed(() => (props.qtypes.length === 1 && props.qtypes[0] === "Multiple Choice" ? 1 : 2.5));
const estMin = computed(() => Math.max(5, Math.round(props.count * avgMinPerQ.value)));
const readinessGain = computed(() =>
  Math.min(8, Math.max(2, Math.round(props.count / 5) + (props.difficulty === "Hard" ? 3 : props.difficulty === "Medium" ? 2 : 1))),
);
const canStart = computed(() => !!props.subject && props.topics.length > 0);

const SOURCES: { id: QuestionSource; label: string; desc: string }[] = [
  { id: "bank", label: "Preppy Question Bank", desc: "Curated to your syllabus." },
  { id: "past-papers", label: "Past Papers", desc: "Real board questions." },
  { id: "ai", label: "AI Generated", desc: "Fresh questions on demand." },
  { id: "mixed", label: "Mixed Source", desc: "Best variety." },
];
const MODES: { id: LearningMode; label: string; desc: string }[] = [
  { id: "guided", label: "Guided Learning", desc: "Hints, sample answers, mark scheme after each." },
  { id: "focused", label: "Focused Practice", desc: "Minimal aids, mark scheme after each." },
  { id: "challenge", label: "Challenge Mode", desc: "No aids until the end." },
];
const FEEDBACK: { id: FeedbackDepth; label: string; desc: string }[] = [
  { id: "instant", label: "Instant", desc: "Grade after every answer." },
  { id: "detailed", label: "Detailed", desc: "Full examiner feedback at the end." },
  { id: "off", label: "Off", desc: "Score only." },
];
</script>

<template>
  <AppShell title="New Learn session" :back="{ to: '/learn', label: 'Back to Learn Mode' }">
    <div class="max-w-[1080px] mx-auto space-y-5 pb-32 lg:pb-8">
      <div class="reveal-up">
        <p class="label-mono mb-1">Configure your session</p>
        <h2 class="text-[22px] lg:text-[26px] font-bold text-ink tracking-[-0.02em]">Set everything up on one page, then start.</h2>
        <p class="text-[13px] text-ink3 mt-1">{{ level }} · {{ examBoard }} · {{ subjects.length }} subject{{ subjects.length === 1 ? "" : "s" }} available.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <SetupCard title="Subject" sub="Pick one subject for this session.">
          <PillGrid :options="subjects" :model-value="subject" :cols="3" @update:model-value="emit('update:subject', $event)" />
        </SetupCard>

        <SetupCard title="Topics" :sub="subject ? `Choose one, many, or all ${subject} topics.` : 'Select a subject first.'">
          <template v-if="subject">
            <div class="flex items-center justify-between mb-2">
              <p class="text-[12px] text-ink3">{{ topics.length === 0 ? "No topics selected" : `${topics.length} selected` }}</p>
              <button
                class="text-[12px] text-violet font-medium px-2 h-9 rounded-md border border-violet-light bg-violet-light/40 hover:bg-violet-light"
                @click="allSelected ? emit('clear-topics') : emit('set-all-topics', allTopics)"
              >
                {{ allSelected ? "Clear all" : "Select all" }}
              </button>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="t in allTopics"
                :key="t"
                :class="`px-3 min-h-11 text-[13px] font-medium rounded-lg border-2 transition flex items-center justify-between gap-2 ${
                  topics.includes(t) ? 'bg-violet-light border-violet text-violet' : 'bg-bg-card border-border text-ink2 hover:border-violet'
                }`"
                @click="emit('toggle-topic', t)"
              >
                <span class="truncate">{{ t }}</span>
                <Check v-if="topics.includes(t)" :size="13" class="shrink-0" />
              </button>
            </div>
          </template>
          <p v-else class="text-[13px] text-ink3">Pick a subject to see its topics.</p>
        </SetupCard>

        <SetupCard title="Paper / Structure" sub="Which question structure to draw from.">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              v-for="q in LEARN_QTYPE_LABELS"
              :key="q"
              :class="`text-left p-3 rounded-lg border-2 transition min-h-[52px] flex items-center justify-between gap-3 ${
                qtypes.includes(q) ? 'bg-violet-light border-violet' : 'bg-bg-card border-border hover:border-violet'
              }`"
              @click="emit('toggle-qtype', q)"
            >
              <span class="text-[13px] font-semibold text-ink">{{ q }}</span>
              <span :class="`w-5 h-5 rounded grid place-items-center border shrink-0 ${qtypes.includes(q) ? 'bg-violet border-violet text-white' : 'bg-bg-card border-border text-ink4'}`">
                <Check v-if="qtypes.includes(q)" :size="12" :stroke-width="3" />
              </span>
            </button>
          </div>
          <p class="text-[11px] text-ink3 mt-2">Leave empty for all types.</p>
        </SetupCard>

        <SetupCard title="Question Source" sub="Where questions are drawn from.">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              v-for="s in SOURCES"
              :key="s.id"
              :class="`text-left p-3 rounded-lg border-2 transition ${questionSource === s.id ? 'bg-violet-light border-violet' : 'bg-bg-card border-border hover:border-violet'}`"
              @click="emit('update:questionSource', s.id)"
            >
              <p class="text-[13px] font-semibold text-ink">{{ s.label }}</p>
              <p class="text-[11px] text-ink3 mt-0.5">{{ s.desc }}</p>
            </button>
          </div>
        </SetupCard>

        <SetupCard title="Difficulty & Volume" sub="Tune the challenge and length.">
          <p class="text-[12px] font-medium text-ink2 mb-2">Difficulty</p>
          <PillGrid :options="[...DIFFICULTIES]" :model-value="difficulty" :cols="4" @update:model-value="emit('update:difficulty', $event as LearnDifficulty)" />
          <p class="text-[12px] font-medium text-ink2 mt-4 mb-2">Number of questions</p>
          <PillGrid :options="COUNTS.map(String)" :model-value="String(count)" :cols="4" @update:model-value="emit('update:count', Number($event))" />
        </SetupCard>

        <SetupCard title="Learning Mode" sub="How much support you get during the session.">
          <div class="space-y-2">
            <button
              v-for="m in MODES"
              :key="m.id"
              :class="`w-full text-left p-3 rounded-lg border-2 transition flex items-start gap-3 ${
                learningMode === m.id ? 'bg-violet-light border-violet' : 'bg-bg-card border-border hover:border-violet'
              }`"
              @click="emit('update:learningMode', m.id)"
            >
              <span :class="`w-4 h-4 mt-0.5 rounded-full border-2 grid place-items-center shrink-0 ${learningMode === m.id ? 'border-violet' : 'border-border'}`">
                <span v-if="learningMode === m.id" class="w-2 h-2 rounded-full bg-violet" />
              </span>
              <div>
                <p class="text-[13px] font-semibold text-ink">{{ m.label }}</p>
                <p class="text-[11.5px] text-ink3 mt-0.5">{{ m.desc }}</p>
              </div>
            </button>
          </div>
        </SetupCard>

        <SetupCard title="Timed / Untimed" sub="Turn on for pacing pressure.">
          <div class="flex items-center justify-between p-3 rounded-lg bg-bg-soft border border-border">
            <div class="flex items-center gap-2">
              <TimerIcon :size="14" class="text-ink3" />
              <span class="text-[13px] font-medium text-ink">Timed session</span>
            </div>
            <ToggleSwitch :on="timed" accent="violet" @update:on="emit('update:timed', $event)" />
          </div>
          <div v-if="timed" class="mt-3">
            <p class="text-[12px] font-medium text-ink2 mb-2">Practice timer</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button
                v-for="t in PRACTICE_TIMERS.filter((t) => t.v > 0)"
                :key="t.v"
                :class="`px-3 h-11 text-[13px] font-medium rounded-lg border transition ${
                  practiceMin === t.v ? 'bg-violet-light border-violet text-violet' : 'bg-bg-card border-border text-ink2 hover:border-violet'
                }`"
                @click="emit('update:practiceMin', t.v)"
              >
                {{ t.l }}
              </button>
            </div>
          </div>
        </SetupCard>

        <SetupCard title="Answer Input Method" sub="Choose how you'll write your answers.">
          <div class="grid grid-cols-2 gap-2">
            <button
              :class="`text-left p-3 rounded-lg border-2 transition flex items-start gap-3 min-h-[76px] ${
                answerMethod === 'keyboard' ? 'bg-violet-light border-violet' : 'bg-bg-card border-border hover:border-violet'
              }`"
              @click="emit('update:answerMethod', 'keyboard')"
            >
              <span class="w-8 h-8 rounded-md bg-violet-light grid place-items-center shrink-0"><Keyboard :size="14" class="text-violet" /></span>
              <div>
                <p class="text-[13px] font-semibold text-ink">Keyboard Typing</p>
                <p class="text-[11px] text-ink3 mt-0.5">Type answers on any device.</p>
              </div>
            </button>
            <button
              :class="`text-left p-3 rounded-lg border-2 transition flex items-start gap-3 min-h-[76px] ${
                answerMethod === 'handwritten' ? 'bg-violet-light border-violet' : 'bg-bg-card border-border hover:border-violet'
              }`"
              @click="emit('update:answerMethod', 'handwritten')"
            >
              <span class="w-8 h-8 rounded-md bg-violet-light grid place-items-center shrink-0"><PenLine :size="14" class="text-violet" /></span>
              <div>
                <p class="text-[13px] font-semibold text-ink">Handwritten</p>
                <p class="text-[11px] text-ink3 mt-0.5">Write by hand on a pad.</p>
              </div>
            </button>
          </div>
        </SetupCard>

        <SetupCard title="AI Feedback" sub="When and how much feedback you get.">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
            <button
              v-for="f in FEEDBACK"
              :key="f.id"
              :class="`text-left p-3 rounded-lg border-2 transition ${feedbackDepth === f.id ? 'bg-violet-light border-violet' : 'bg-bg-card border-border hover:border-violet'}`"
              @click="emit('update:feedbackDepth', f.id)"
            >
              <p class="text-[13px] font-semibold text-ink">{{ f.label }}</p>
              <p class="text-[11px] text-ink3 mt-0.5">{{ f.desc }}</p>
            </button>
          </div>
          <div class="flex items-center justify-between p-3 rounded-lg bg-bg-soft border border-border">
            <div class="flex items-center gap-2">
              <Zap :size="14" class="text-violet" />
              <div>
                <p class="text-[13px] font-medium text-ink">Instant explanations</p>
                <p class="text-[11px] text-ink3">Reveal a short 'why' after each answer.</p>
              </div>
            </div>
            <ToggleSwitch :on="instantExplanation" accent="violet" @update:on="emit('update:instantExplanation', $event)" />
          </div>
        </SetupCard>
      </div>

      <div class="bg-gradient-to-br from-violet-light to-blue-light/40 border border-violet-light rounded-2xl p-5 lg:p-6 reveal-up">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-white/70 grid place-items-center shrink-0"><Clock :size="16" class="text-violet" /></span>
            <div>
              <p class="label-mono">Estimated time</p>
              <p class="font-mono text-[20px] text-ink font-medium mt-0.5">{{ estMin }} min</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-white/70 grid place-items-center shrink-0"><TrendingUp :size="16" class="text-violet" /></span>
            <div>
              <p class="label-mono">Predicted readiness gain</p>
              <p class="font-mono text-[20px] text-ink font-medium mt-0.5">+{{ readinessGain }}%</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-white/70 grid place-items-center shrink-0"><Sparkles :size="16" class="text-violet" /></span>
            <div>
              <p class="label-mono">Session preview</p>
              <p class="text-[13px] text-ink font-medium mt-0.5">{{ subject || "—" }} · {{ topicLabel }} · {{ difficulty }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="hidden lg:block">
        <button
          :disabled="!canStart"
          class="w-full inline-flex items-center justify-center gap-2 bg-violet text-white text-[15px] font-semibold h-14 rounded-lg hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
          @click="emit('start')"
        >
          Start Learn Mode <ArrowRight :size="16" />
        </button>
        <p v-if="!canStart" class="text-[12px] text-ink3 text-center mt-2">Pick a subject and at least one topic to continue.</p>
      </div>
    </div>

    <div class="lg:hidden fixed bottom-[58px] inset-x-0 z-30 px-4 py-3 bg-bg/95 backdrop-blur border-t border-border">
      <button
        :disabled="!canStart"
        class="w-full inline-flex items-center justify-center gap-2 bg-violet text-white text-[15px] font-semibold h-12 rounded-lg disabled:opacity-40"
        @click="emit('start')"
      >
        Start Learn Mode <ArrowRight :size="16" />
      </button>
    </div>
  </AppShell>
</template>
