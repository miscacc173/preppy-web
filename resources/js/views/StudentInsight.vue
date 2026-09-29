<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import {
  Sparkles, Target, TrendingUp, AlertTriangle, Lightbulb, ArrowRight, CheckCircle2,
  BookOpen, BarChart3, Timer, Brain, Gauge, Gift,
} from "lucide-vue-next";
import { loadOnboardingFromApi, loadAssessment, estimateLevel, type StudentOnboarding, type AssessmentResult } from "@/lib/studentPlan";
import { EXAMINER_NOTES, WEAK_AREAS } from "@/lib/questionBank";
import BigMetric from "./student-insight/BigMetric.vue";
import InsightCard from "./student-insight/InsightCard.vue";
import Preview from "./student-insight/Preview.vue";

setPageMeta({
  title: "Your first AI insight — Preppy",
  description: "Your personalised AI Star Prep insight, based on your onboarding answers and your first short assessment.",
});

const router = useRouter();
const o = ref<StudentOnboarding | null>(null);
const r = ref<AssessmentResult | null>(null);
const revealed = ref(false);

onMounted(async () => {
  const ob = await loadOnboardingFromApi().catch(() => null);
  const as = loadAssessment();
  if (!ob) {
    router.push("/student");
    return;
  }
  if (!as) {
    router.push("/student-plan");
    return;
  }
  o.value = ob;
  r.value = as;
  setTimeout(() => (revealed.value = true), 60);
});

const pct = computed(() => (r.value ? Math.round((r.value.correct / r.value.total) * 100) : 0));
const readiness = computed(() => Math.max(20, Math.min(95, pct.value)));
const currentLevel = computed(() => (o.value ? estimateLevel(pct.value, o.value.level) : ""));
const targetGrade = computed(() => (r.value?.subject && o.value ? o.value.targets[r.value.subject] : undefined));
const notes = computed(() => (r.value?.bankSubject ? EXAMINER_NOTES[r.value.bankSubject] : null));
const weakAreas = computed(() => (r.value?.bankSubject ? WEAK_AREAS[r.value.bankSubject] : []));

const strength = computed(() => {
  if (!r.value) return "";
  const half = Math.ceil(r.value.answers.length / 2);
  const firstHalfCorrect = r.value.answers.slice(0, half).filter((a) => a.correct).length;
  const secondHalfCorrect = r.value.answers.slice(half).filter((a) => a.correct).length;
  return firstHalfCorrect >= secondHalfCorrect ? "Applying core concepts to standard questions" : "Building momentum as questions get harder";
});
const attention = computed(() => {
  if (!r.value) return "";
  const half = Math.ceil(r.value.answers.length / 2);
  const firstHalfCorrect = r.value.answers.slice(0, half).filter((a) => a.correct).length;
  const secondHalfCorrect = r.value.answers.slice(half).filter((a) => a.correct).length;
  return firstHalfCorrect >= secondHalfCorrect ? (weakAreas.value[0]?.t ?? "Multi-step reasoning under time pressure") : "Consistency on foundational questions";
});

const PREVIEWS = [
  { icon: Sparkles, title: "Personalised recommendations" },
  { icon: BookOpen, title: "Exam-board-aligned practice" },
  { icon: Brain, title: "AI feedback on your answers" },
  { icon: AlertTriangle, title: "Weak-topic identification" },
  { icon: Gauge, title: "Readiness tracking" },
  { icon: BarChart3, title: "Progress history" },
  { icon: Lightbulb, title: "Learn Mode" },
  { icon: Timer, title: "Exam Mode" },
];
</script>

<template>
  <div v-if="o && r" class="min-h-screen bg-bg flex flex-col">
    <TopNav />

    <main class="flex-1">
      <section class="px-6 pt-14 pb-8 border-b border-border bg-[#FAFBFC]">
        <div class="max-w-[880px] mx-auto text-center">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-light text-violet text-[11.5px] font-semibold mb-4">
            <Sparkles :size="12" /> AI insight ready
          </span>
          <h1 class="text-[30px] md:text-[40px] font-bold text-ink tracking-[-0.03em] leading-[1.1] mb-4">
            Your first <span class="text-violet">AI learning insight</span> is ready
          </h1>
          <p class="text-[15px] text-ink2 leading-[1.65] font-light max-w-[600px] mx-auto">
            Based on {{ r.total }} short questions in {{ r.subject }}. This is an initial estimate — it will become more accurate as you practise.
          </p>
        </div>
      </section>

      <div :class="`max-w-[880px] mx-auto px-5 md:px-6 py-10 md:py-14 space-y-8 transition-all duration-500 ${revealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'}`">
        <!-- Top metrics -->
        <section class="grid sm:grid-cols-3 gap-3">
          <BigMetric :icon="Gauge" tone="blue" label="Estimated readiness" :value="`${readiness}%`" :sub="`${r.correct} of ${r.total} correct`" />
          <BigMetric :icon="BarChart3" tone="violet" label="Current estimated level" :value="currentLevel" sub="Based on this short assessment" />
          <BigMetric :icon="Target" tone="gold" label="Target grade" :value="targetGrade ?? 'Not set'" :sub="targetGrade ? 'From your onboarding' : 'Add one in Settings'" />
        </section>

        <!-- Strength / Attention / AI note -->
        <section class="bg-bg-card border border-border rounded-[20px] p-6 md:p-8 shadow-[0_4px_20px_rgba(15,23,42,0.04)]">
          <div class="grid md:grid-cols-2 gap-5">
            <InsightCard
              :icon="TrendingUp"
              tone="mint"
              label="Strength"
              :title="strength"
              body="You're consistent in the areas above. Keep them warm with lighter practice while focusing energy on the gap below."
            />
            <InsightCard
              :icon="AlertTriangle"
              tone="amber"
              label="Needs attention"
              :title="attention"
              body="This is where a focused Learn Mode session will move the needle fastest — small gains here often lift your overall grade."
            />
          </div>

          <div class="mt-6 p-5 rounded-2xl bg-violet-light/60 border border-violet/15">
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-xl bg-white grid place-items-center shrink-0">
                <Brain :size="16" class="text-violet" />
              </div>
              <div>
                <p class="label-mono text-violet mb-1">AI observation</p>
                <p class="text-[14px] text-ink leading-[1.6] font-medium">
                  {{ notes ?? `Based on this short assessment, your current performance in ${r.subject} is estimated around ${currentLevel}. With focused practice on the areas above, you can work steadily towards${targetGrade ? ` ${targetGrade}` : " your target grade"}.` }}
                </p>
                <p class="mt-2 text-[12.5px] text-ink3 font-light">This estimate will refine as you complete more sessions.</p>
              </div>
            </div>
          </div>

          <div class="mt-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 p-5 rounded-2xl border border-border bg-bg-soft/50">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-light grid place-items-center shrink-0">
                <Lightbulb :size="17" class="text-blue" />
              </div>
              <div>
                <p class="label-mono text-blue mb-0.5">Recommended next step</p>
                <p class="text-[14px] font-semibold text-ink">Focused {{ r.subject }} Learn Mode session</p>
                <p class="text-[12.5px] text-ink3 font-light">~15 minutes • Targets the gap identified above</p>
              </div>
            </div>
            <RouterLink to="/learn" class="inline-flex items-center justify-center gap-2 bg-blue text-white text-[13.5px] font-semibold px-5 py-2.5 rounded-full hover:bg-blue/90 transition">
              Open Learn Mode <ArrowRight :size="14" />
            </RouterLink>
          </div>
        </section>

        <!-- This is only the beginning -->
        <section class="relative overflow-hidden bg-bg-card border border-border rounded-[20px] p-6 md:p-10 shadow-[0_10px_40px_-12px_rgba(124,79,224,0.18)]">
          <div aria-hidden class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-violet-light blur-3xl opacity-70 pointer-events-none" />
          <div class="relative">
            <p class="label-mono text-violet mb-2">Your journey with AI Star Prep</p>
            <h2 class="text-[26px] md:text-[32px] font-bold text-ink tracking-[-0.02em] mb-3">This is only the beginning</h2>
            <p class="text-[14.5px] text-ink2 font-light leading-[1.65] max-w-[620px] mb-7">
              AI Star Prep continues adapting your learning plan as you practise, complete sessions and prepare for your exams.
            </p>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2.5 mb-7">
              <Preview v-for="p in PREVIEWS" :key="p.title" :icon="p.icon" :title="p.title" />
            </div>

            <div class="mb-7 flex items-start gap-3 p-4 rounded-2xl border border-dashed border-blue/40 bg-blue-light/40">
              <div class="w-9 h-9 rounded-xl bg-white grid place-items-center shrink-0">
                <Gift :size="16" class="text-blue" />
              </div>
              <div>
                <p class="text-[13.5px] font-semibold text-ink">Your personalised learning journey includes limited free access</p>
                <p class="text-[12.5px] text-ink3 font-light mt-0.5">Explore the full learning experience for a limited time — no commitment needed to start.</p>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
              <RouterLink
                to="/dashboard"
                class="inline-flex items-center justify-center gap-2 bg-blue text-white text-[14px] font-semibold px-6 py-3 rounded-full hover:bg-blue/90 hover:shadow-[0_10px_30px_-8px_rgba(59,110,248,0.45)] transition-all"
              >
                Continue my learning journey <ArrowRight :size="16" />
              </RouterLink>
              <RouterLink
                to="/student-plan"
                class="inline-flex items-center justify-center gap-2 bg-bg-card border border-border text-ink2 text-[14px] font-semibold px-6 py-3 rounded-full hover:border-blue-mid hover:text-ink transition"
              >
                View my learning plan
              </RouterLink>
            </div>

            <p class="mt-5 flex items-start gap-1.5 text-[11.5px] text-ink3 font-light">
              <CheckCircle2 :size="12" class="text-mint mt-0.5" />
              Estimates are indicative and will refine as you complete more sessions. AI Star Prep doesn't guarantee grades — it helps you work toward them.
            </p>
          </div>
        </section>
      </div>
    </main>

    <Footer />
  </div>
</template>
