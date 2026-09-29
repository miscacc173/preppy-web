<script setup lang="ts">
import { computed } from "vue";
import AppShell from "@/components/app/AppShell.vue";
import { Timer as TimerIcon, AlertTriangle, Keyboard, PenLine, Zap, Sparkles, Clock, TrendingUp } from "lucide-vue-next";
import type { AnswerMethod, FeedbackDepth } from "@/lib/wizardStore";
import SetupCard from "@/views/learn-session/SetupCard.vue";
import PillGrid from "@/views/learn-session/PillGrid.vue";
import ToggleSwitch from "@/views/learn-session/ToggleSwitch.vue";
import { papersForSubject, type Paper } from "./data";

const props = defineProps<{
  subjects: string[];
  level: string;
  examBoard: string;
  subject: string;
  paper: Paper | null;
  answerMethod: AnswerMethod;
  instantExplanation: boolean;
  feedbackDepth: FeedbackDepth;
}>();

const emit = defineEmits<{
  "update:subject": [string];
  "update:paper": [Paper | null];
  "update:answerMethod": [AnswerMethod];
  "update:instantExplanation": [boolean];
  "update:feedbackDepth": [FeedbackDepth];
  start: [];
}>();

const papers = computed(() => papersForSubject(props.subject));
const canStart = computed(() => !!props.subject && !!props.paper);
const readinessGain = computed(() => (props.paper ? Math.min(6, Math.max(2, Math.round(props.paper.questions / 4))) : 3));

const FEEDBACK: { id: FeedbackDepth; label: string; desc: string }[] = [
  { id: "instant", label: "Instant", desc: "Grade during exam." },
  { id: "detailed", label: "Detailed", desc: "Full examiner report at end." },
  { id: "off", label: "Off", desc: "Score only." },
];
</script>

<template>
  <AppShell title="New mock exam" :back="{ to: '/exam', label: 'Back to Exam Mode' }">
    <div class="max-w-[1080px] mx-auto space-y-5 pb-32 lg:pb-8">
      <div class="reveal-up">
        <p class="label-mono mb-1">Configure your exam</p>
        <h2 class="text-[22px] lg:text-[26px] font-bold text-ink tracking-[-0.02em]">Everything on one page — official timing auto-applied.</h2>
        <p class="text-[13px] text-ink3 mt-1">{{ level }} · {{ examBoard }} · {{ subjects.length }} subject{{ subjects.length === 1 ? "" : "s" }} available.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <SetupCard title="Subject" sub="Pick one subject for this mock.">
          <PillGrid :options="subjects" :model-value="subject" @update:model-value="(v) => { emit('update:subject', v); emit('update:paper', null); }" />
        </SetupCard>

        <SetupCard title="Paper / Structure" :sub="subject ? `${subject} · ${level}` : 'Pick a subject first.'">
          <p v-if="!subject" class="text-[13px] text-ink3">Select a subject to see its papers.</p>
          <p v-else-if="papers.length === 0" class="text-[13px] text-ink3">No papers configured for {{ subject }} yet.</p>
          <div v-else class="space-y-2">
            <button
              v-for="p in papers"
              :key="p.id"
              :class="`w-full text-left p-3 rounded-xl border-2 transition flex items-center justify-between gap-3 ${
                paper?.id === p.id ? 'bg-gold-light/50 border-gold' : 'bg-bg-card border-border hover:border-gold'
              }`"
              @click="emit('update:paper', p)"
            >
              <div class="min-w-0">
                <p class="text-[13.5px] font-semibold text-ink truncate">{{ p.name }}</p>
                <p class="text-[11.5px] text-ink3 mt-0.5">{{ p.questions }} questions · official timing</p>
              </div>
              <span class="font-mono text-[12px] text-gold bg-gold-light border border-gold-light rounded-full px-2.5 py-0.5 shrink-0">
                {{ Math.floor(p.minutes / 60) ? `${Math.floor(p.minutes / 60)}h ` : "" }}{{ p.minutes % 60 ? `${p.minutes % 60}m` : "" }}
              </span>
            </button>
          </div>
        </SetupCard>

        <SetupCard title="Question Source" sub="Exam mode uses official past-paper style questions.">
          <div class="p-3 rounded-lg bg-gold-light/40 border border-gold-light">
            <p class="text-[13px] font-semibold text-ink">Past Papers</p>
            <p class="text-[11.5px] text-ink3 mt-0.5">Real board-style questions matched to your paper structure.</p>
          </div>
        </SetupCard>

        <SetupCard title="Number of Questions & Difficulty" sub="Auto-populated by paper.">
          <div class="grid grid-cols-2 gap-2">
            <div class="p-3 rounded-lg bg-bg-soft border border-border">
              <p class="label-mono">Questions</p>
              <p class="font-mono text-[18px] text-ink mt-0.5">{{ paper?.questions ?? "—" }}</p>
            </div>
            <div class="p-3 rounded-lg bg-bg-soft border border-border">
              <p class="label-mono">Difficulty</p>
              <p class="font-mono text-[18px] text-ink mt-0.5">Exam</p>
            </div>
          </div>
          <p class="text-[11px] text-ink3 mt-2">Exam mode mirrors real conditions — no manual overrides.</p>
        </SetupCard>

        <SetupCard title="Timed / Untimed" sub="Exam mode is always timed with official duration.">
          <div class="flex items-center justify-between p-3 rounded-lg bg-gold-light/40 border border-gold-light">
            <div class="flex items-center gap-2">
              <TimerIcon :size="14" class="text-gold" />
              <span class="text-[13px] font-medium text-ink">Timed session</span>
            </div>
            <span class="font-mono text-[13px] text-gold font-semibold">{{ paper ? `${paper.minutes} min` : "—" }}</span>
          </div>
        </SetupCard>

        <SetupCard title="Learning Mode Options" sub="Aids allowed during the mock.">
          <div class="space-y-2">
            <div class="p-3 rounded-lg bg-bg-soft border border-border flex items-center justify-between">
              <div>
                <p class="text-[13px] font-medium text-ink">Hints & sample answers</p>
                <p class="text-[11px] text-ink3">Disabled in exam mode.</p>
              </div>
              <span class="text-[11px] font-semibold text-ink3 bg-bg-soft2 rounded-full px-2 py-0.5">OFF</span>
            </div>
            <div class="p-3 rounded-lg bg-bg-soft border border-border flex items-center justify-between">
              <div>
                <p class="text-[13px] font-medium text-ink">Flag & review</p>
                <p class="text-[11px] text-ink3">Revisit any question before submitting.</p>
              </div>
              <span class="text-[11px] font-semibold text-mint bg-mint-light rounded-full px-2 py-0.5">ON</span>
            </div>
          </div>
        </SetupCard>

        <SetupCard title="Answer Input Method" sub="Choose how you'll write your answers.">
          <div class="grid grid-cols-2 gap-2">
            <button
              :class="`text-left p-3 rounded-lg border-2 transition flex items-start gap-3 min-h-[76px] ${
                answerMethod === 'keyboard' ? 'bg-gold-light/60 border-gold' : 'bg-bg-card border-border hover:border-gold'
              }`"
              @click="emit('update:answerMethod', 'keyboard')"
            >
              <span class="w-8 h-8 rounded-md bg-gold-light grid place-items-center shrink-0"><Keyboard :size="14" class="text-gold" /></span>
              <div>
                <p class="text-[13px] font-semibold text-ink">Keyboard Typing</p>
                <p class="text-[11px] text-ink3 mt-0.5">Type answers on any device.</p>
              </div>
            </button>
            <button
              :class="`text-left p-3 rounded-lg border-2 transition flex items-start gap-3 min-h-[76px] ${
                answerMethod === 'handwritten' ? 'bg-gold-light/60 border-gold' : 'bg-bg-card border-border hover:border-gold'
              }`"
              @click="emit('update:answerMethod', 'handwritten')"
            >
              <span class="w-8 h-8 rounded-md bg-gold-light grid place-items-center shrink-0"><PenLine :size="14" class="text-gold" /></span>
              <div>
                <p class="text-[13px] font-semibold text-ink">Handwritten</p>
                <p class="text-[11px] text-ink3 mt-0.5">Write by hand — closest to the real exam.</p>
              </div>
            </button>
          </div>
        </SetupCard>

        <SetupCard title="AI Feedback" sub="How much feedback you get after submitting.">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-3">
            <button
              v-for="f in FEEDBACK"
              :key="f.id"
              :class="`text-left p-3 rounded-lg border-2 transition ${feedbackDepth === f.id ? 'bg-gold-light/60 border-gold' : 'bg-bg-card border-border hover:border-gold'}`"
              @click="emit('update:feedbackDepth', f.id)"
            >
              <p class="text-[13px] font-semibold text-ink">{{ f.label }}</p>
              <p class="text-[11px] text-ink3 mt-0.5">{{ f.desc }}</p>
            </button>
          </div>
          <div class="flex items-center justify-between p-3 rounded-lg bg-bg-soft border border-border">
            <div class="flex items-center gap-2">
              <Zap :size="14" class="text-gold" />
              <div>
                <p class="text-[13px] font-medium text-ink">Instant explanations</p>
                <p class="text-[11px] text-ink3">Reveal a short 'why' with each result.</p>
              </div>
            </div>
            <ToggleSwitch :on="instantExplanation" accent="gold" @update:on="emit('update:instantExplanation', $event)" />
          </div>
        </SetupCard>
      </div>

      <div class="bg-gradient-to-br from-gold-light to-bg-card border border-gold-light rounded-2xl p-5 lg:p-6 reveal-up">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
          <div class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-white/70 grid place-items-center shrink-0"><Clock :size="16" class="text-gold" /></span>
            <div>
              <p class="label-mono">Estimated time</p>
              <p class="font-mono text-[20px] text-ink font-medium mt-0.5">{{ paper ? `${paper.minutes} min` : "—" }}</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-white/70 grid place-items-center shrink-0"><TrendingUp :size="16" class="text-gold" /></span>
            <div>
              <p class="label-mono">Predicted readiness gain</p>
              <p class="font-mono text-[20px] text-ink font-medium mt-0.5">+{{ readinessGain }}%</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-lg bg-white/70 grid place-items-center shrink-0"><Sparkles :size="16" class="text-gold" /></span>
            <div>
              <p class="label-mono">Session preview</p>
              <p class="text-[13px] text-ink font-medium mt-0.5">{{ subject || "—" }} · {{ paper?.name ?? "Pick a paper" }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white/60 border border-gold-light rounded-xl p-4 flex gap-3">
          <AlertTriangle :size="16" class="text-gold mt-0.5 shrink-0" />
          <p class="text-[12.5px] text-ink2">
            <strong class="text-ink">Exam Mode is timed.</strong> No hints or AI assistance during the paper.
            The timer continues if you leave the tab. Submit early at any time.
          </p>
        </div>
      </div>

      <div class="hidden lg:block">
        <button
          :disabled="!canStart"
          class="w-full inline-flex items-center justify-center gap-2 bg-gold text-white text-[15px] font-semibold h-14 rounded-lg hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
          @click="emit('start')"
        >
          Start Exam Mode <TimerIcon :size="16" />
        </button>
        <p v-if="!canStart" class="text-[12px] text-ink3 text-center mt-2">Pick a subject and paper to continue.</p>
      </div>
    </div>

    <div class="lg:hidden fixed bottom-[58px] inset-x-0 z-30 px-4 py-3 bg-bg/95 backdrop-blur border-t border-border">
      <button
        :disabled="!canStart"
        class="w-full inline-flex items-center justify-center gap-2 bg-gold text-white text-[15px] font-semibold h-12 rounded-lg disabled:opacity-40"
        @click="emit('start')"
      >
        Start Exam Mode <TimerIcon :size="16" />
      </button>
    </div>
  </AppShell>
</template>
