<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import {
  Sparkles, GraduationCap, Target, CalendarDays, BookOpen, AlertCircle, ArrowRight,
  CheckCircle2, ClipboardList, Clock,
} from "lucide-vue-next";
import { loadOnboardingFromApi, pickPrioritySubject, toBankSubject, monthsUntil, formatExamPeriod, type StudentOnboarding } from "@/lib/studentPlan";
import Stat from "./student-plan/Stat.vue";
import MiniStat from "./student-plan/MiniStat.vue";
import Chip from "./student-plan/Chip.vue";

setPageMeta({
  title: "Your personalised plan — AiStar Prep",
  description: "Your AI Star Prep learning plan, built around your subjects, target grades and exam timeline.",
});

const LOADING_STEPS = [
  "Building your personalised learning plan…",
  "Reviewing your subjects and target grades…",
  "Prioritising the areas that need the most attention…",
  "Preparing your recommended starting point…",
];

const router = useRouter();
const onboarding = ref<StudentOnboarding | null>(null);
const ready = ref(false);
const progress = ref(0);
const stepIdx = ref(0);
let raf = 0;

onMounted(async () => {
  const o = await loadOnboardingFromApi().catch(() => null);
  if (!o) {
    router.push("/student");
    return;
  }
  onboarding.value = o;

  let start: number | null = null;
  const total = 2400;
  const tick = (t: number) => {
    if (start === null) start = t;
    const p = Math.min(1, (t - start) / total);
    progress.value = Math.round(p * 100);
    stepIdx.value = Math.min(LOADING_STEPS.length - 1, Math.floor(p * LOADING_STEPS.length));
    if (p < 1) raf = requestAnimationFrame(tick);
    else setTimeout(() => (ready.value = true), 250);
  };
  raf = requestAnimationFrame(tick);
});
onBeforeUnmount(() => cancelAnimationFrame(raf));

const priority = computed(() => (onboarding.value ? pickPrioritySubject(onboarding.value) : null));
const bank = computed(() => toBankSubject(priority.value));
const bankUnavailable = computed(() => !bank.value && !!priority.value);
const months = computed(() => (onboarding.value ? monthsUntil(onboarding.value.examMonth, onboarding.value.examYear) : null));
const timeRemaining = computed(() =>
  months.value === null ? "Set an exam date to track this" : months.value <= 0 ? "Exam window has begun" : `${months.value} month${months.value === 1 ? "" : "s"}`,
);
const targetForPriority = computed(() => (priority.value && onboarding.value ? onboarding.value.targets[priority.value] : undefined));

function confidenceText(c?: "low" | "mid" | "high") {
  if (c === "low") return "Needs support";
  if (c === "mid") return "Somewhat confident";
  if (c === "high") return "Confident";
  return "Not set";
}
const confidenceLabel = computed(() => confidenceText(priority.value && onboarding.value ? onboarding.value.confidence[priority.value] : undefined));

function scrollTop(e: Event) {
  e.preventDefault();
  window.scrollTo({ top: 0, behavior: "smooth" });
}
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <TopNav />

    <main class="flex-1">
      <template v-if="onboarding">
        <!-- Stage 1: loading -->
        <section v-if="!ready" class="px-6 py-24 md:py-32">
          <div class="max-w-[560px] mx-auto text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-light grid place-items-center mb-6 animate-scale-in">
              <Sparkles :size="22" class="text-violet" />
            </div>
            <p class="label-mono mb-3 text-violet">Personalising</p>
            <h1 class="text-[26px] md:text-[32px] font-bold text-ink tracking-[-0.03em] leading-[1.15] mb-6">Building your learning plan</h1>

            <div class="h-2 rounded-full bg-bg-soft overflow-hidden mb-4">
              <div class="h-full bg-gradient-to-r from-blue to-violet transition-all duration-200 ease-out" :style="{ width: `${progress}%` }" />
            </div>

            <div :key="stepIdx" class="min-h-[24px] text-[13.5px] text-ink2 font-light animate-fade-in">
              {{ LOADING_STEPS[stepIdx] }}
            </div>
          </div>
        </section>

        <!-- Stage 2 + 3: plan reveal -->
        <template v-else>
          <section class="px-6 pt-16 pb-10 border-b border-border bg-[#FAFBFC] animate-fade-in">
            <div class="max-w-[880px] mx-auto text-center">
              <p class="label-mono mb-3 text-blue">Your personalised learning plan</p>
              <h1 class="text-[32px] md:text-[42px] font-bold text-ink tracking-[-0.03em] leading-[1.1] mb-4">
                Your learning plan is <span class="text-blue">ready</span>
              </h1>
              <p class="text-[15px] text-ink2 leading-[1.65] font-light max-w-[620px] mx-auto">
                Built around your subjects, current confidence, target grades and exam timeline.
              </p>
            </div>
          </section>

          <div class="max-w-[880px] mx-auto px-5 md:px-6 py-10 md:py-14 space-y-8">
            <!-- Summary card -->
            <section class="bg-bg-card border border-border rounded-[20px] p-6 md:p-8 shadow-[0_4px_20px_rgba(15,23,42,0.04)] animate-fade-in" style="animation-delay: 60ms; animation-fill-mode: backwards">
              <div class="flex items-start gap-4 mb-6">
                <div class="w-11 h-11 rounded-xl bg-blue-light grid place-items-center shrink-0">
                  <GraduationCap :size="20" class="text-blue" />
                </div>
                <div>
                  <p class="label-mono text-blue mb-1">Your setup</p>
                  <h2 class="text-[19px] md:text-[21px] font-bold text-ink tracking-[-0.02em]">Personalised summary</h2>
                  <p class="text-[13.5px] text-ink3 font-light mt-1">Everything below comes from your onboarding answers.</p>
                </div>
              </div>

              <div class="grid sm:grid-cols-2 gap-3">
                <Stat label="Qualification">{{ onboarding.level || "—" }}</Stat>
                <Stat label="Exam board">{{ onboarding.board || "—" }}</Stat>
                <Stat label="Selected subjects">{{ onboarding.subjects.length ? onboarding.subjects.join(", ") : "—" }}</Stat>
                <Stat label="Needs most attention" tone="amber">
                  {{ onboarding.notSureWeak ? "AI will identify through practice" : onboarding.weak.length ? onboarding.weak.join(", ") : "Not selected" }}
                </Stat>
                <Stat label="Priority subject" tone="violet">{{ priority ?? "—" }}</Stat>
                <Stat label="Current confidence">{{ confidenceLabel }}</Stat>
                <Stat label="Exam period" :icon="CalendarDays">{{ formatExamPeriod(onboarding) }}</Stat>
                <Stat label="Time remaining" tone="mint" :icon="Clock">{{ timeRemaining }}</Stat>
              </div>

              <div v-if="Object.keys(onboarding.targets).length > 0" class="mt-5 pt-5 border-t border-border">
                <p class="label-mono text-ink3 mb-3">Target grades</p>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="[s, g] in Object.entries(onboarding.targets)"
                    :key="s"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gold-light border border-gold/30 text-[12.5px] font-semibold text-ink"
                  >
                    <Target :size="12" class="text-gold" />
                    {{ s }} <span class="text-gold">— {{ g }}</span>
                  </span>
                </div>
              </div>
            </section>

            <!-- Recommended starting point -->
            <section
              class="relative overflow-hidden bg-bg-card border border-border rounded-[20px] p-6 md:p-8 shadow-[0_10px_40px_-12px_rgba(59,110,248,0.18)] animate-fade-in"
              style="animation-delay: 160ms; animation-fill-mode: backwards"
            >
              <div aria-hidden class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-blue-light blur-3xl opacity-70 pointer-events-none" />

              <div class="relative">
                <p class="label-mono text-violet mb-2">Your recommended starting point</p>
                <h2 class="text-[24px] md:text-[28px] font-bold text-ink tracking-[-0.02em] mb-3">
                  Start with <span class="text-blue">{{ priority ?? "your first subject" }}</span>
                </h2>
                <p class="text-[14.5px] text-ink2 font-light leading-[1.65] mb-6 max-w-[560px]">
                  {{
                    priority
                      ? `You identified ${priority} as the subject that needs the most attention. Complete a short personalised assessment so AI Star Prep can understand your current level and recommend where to begin.`
                      : "Complete a short personalised assessment so AI Star Prep can understand your current level and recommend where to begin."
                  }}
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-7">
                  <MiniStat :icon="BookOpen" label="Subject" :value="priority ?? '—'" />
                  <MiniStat :icon="GraduationCap" label="Qualification" :value="onboarding.level || '—'" />
                  <MiniStat :icon="ClipboardList" label="Exam board" :value="onboarding.board || '—'" />
                  <MiniStat :icon="Clock" label="~ Time" value="5–7 min" />
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-6">
                  <Chip tone="blue">6 questions</Chip>
                  <Chip tone="mint">Low-pressure</Chip>
                  <Chip tone="violet">Adaptive insight</Chip>
                  <Chip v-if="targetForPriority" tone="gold">Target: {{ targetForPriority }}</Chip>
                </div>

                <div v-if="bankUnavailable" class="mb-6 flex items-start gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 text-[12.5px] text-amber-800">
                  <AlertCircle :size="14" class="mt-0.5 shrink-0" />
                  Our assessment library is being expanded for {{ priority }}. You'll receive a general-skills assessment for now.
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                  <RouterLink
                    to="/student-assessment"
                    class="inline-flex items-center justify-center gap-2 bg-blue text-white text-[14px] font-semibold px-6 py-3 rounded-full hover:bg-blue/90 hover:shadow-[0_10px_30px_-8px_rgba(59,110,248,0.45)] transition-all"
                  >
                    Start my quick assessment <ArrowRight :size="16" />
                  </RouterLink>
                  <a
                    href="#top"
                    class="inline-flex items-center justify-center gap-2 bg-bg-card border border-border text-ink2 text-[14px] font-semibold px-6 py-3 rounded-full hover:border-blue-mid hover:text-ink transition"
                    @click="scrollTop"
                  >
                    Review my plan
                  </a>
                </div>
              </div>
            </section>

            <!-- Reassurance -->
            <div class="flex items-start gap-3 text-[12.5px] text-ink3 font-light animate-fade-in" style="animation-delay: 240ms; animation-fill-mode: backwards">
              <CheckCircle2 :size="14" class="text-mint mt-0.5" />
              Your plan updates automatically as you complete sessions. You can revisit these preferences anytime from Settings.
            </div>
          </div>
        </template>
      </template>
    </main>

    <Footer />
  </div>
</template>
