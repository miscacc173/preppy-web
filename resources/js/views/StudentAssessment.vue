<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import { ArrowRight, ArrowLeft, Sparkles, CheckCircle2, XCircle, Lightbulb } from "lucide-vue-next";
import { loadOnboardingFromApi, pickPrioritySubject, pickAssessmentQuestions, saveAssessment, toBankSubject, type StudentOnboarding } from "@/lib/studentPlan";

setPageMeta({
  title: "Quick assessment — AiStar Prep",
  description: "A short, low-pressure personalised assessment to help AI Star Prep understand your current level.",
});

const router = useRouter();
const o = ref<StudentOnboarding | null>(null);
const idx = ref(0);
const selected = ref<number | null>(null);
const submitted = ref(false);
const answers = ref<{ qid: string; correct: boolean }[]>([]);

onMounted(async () => {
  const found = await loadOnboardingFromApi().catch(() => null);
  if (!found) router.push("/student");
  else o.value = found;
});

const priority = computed(() => (o.value ? pickPrioritySubject(o.value) : null));
const bank = computed(() => toBankSubject(priority.value));
const questions = computed(() => (o.value ? pickAssessmentQuestions(bank.value, 6) : []));

const total = computed(() => questions.value.length);
const q = computed(() => questions.value[idx.value]);
const isCorrect = computed(() => selected.value !== null && q.value && selected.value === q.value.a);
const done = computed(() => idx.value >= total.value);
const progressPct = computed(() => (total.value ? Math.round(((idx.value + (submitted.value ? 1 : 0)) / total.value) * 100) : 0));

function handleSubmit() {
  if (selected.value === null || !q.value) return;
  submitted.value = true;
  answers.value = [...answers.value, { qid: q.value.id, correct: selected.value === q.value.a }];
}

function handleNext() {
  submitted.value = false;
  selected.value = null;
  if (idx.value + 1 >= total.value) {
    const correct = answers.value.filter((a) => a.correct).length;
    saveAssessment({
      subject: priority.value ?? "General",
      bankSubject: bank.value,
      total: total.value,
      correct,
      answers: answers.value,
      completedAt: new Date().toISOString(),
    });
    router.push("/student-insight");
    return;
  }
  idx.value += 1;
}
</script>

<template>
  <div v-if="o && questions.length > 0" class="min-h-screen bg-bg flex flex-col">
    <TopNav />

    <main class="flex-1">
      <section class="px-6 pt-10 pb-6 border-b border-border bg-[#FAFBFC]">
        <div class="max-w-[760px] mx-auto">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-violet-light text-violet text-[11.5px] font-semibold">
                <Sparkles :size="11" /> Personalised assessment
              </span>
              <span class="text-[12.5px] text-ink3 font-medium">
                Subject: <span class="text-ink font-semibold">{{ priority ?? "General" }}</span>
              </span>
            </div>
            <span class="text-[12.5px] text-ink3">
              Question <span class="text-ink font-semibold">{{ Math.min(idx + 1, total) }}</span> of {{ total }}
            </span>
          </div>

          <div class="h-1.5 rounded-full bg-bg-soft overflow-hidden">
            <div class="h-full bg-gradient-to-r from-blue to-violet transition-all duration-500" :style="{ width: `${progressPct}%` }" />
          </div>
        </div>
      </section>

      <div class="max-w-[760px] mx-auto px-5 md:px-6 py-8 md:py-12">
        <section v-if="!done" :key="idx" class="bg-bg-card border border-border rounded-[20px] p-6 md:p-8 shadow-[0_4px_20px_rgba(15,23,42,0.04)] animate-fade-in">
          <p class="label-mono text-blue mb-3">Multiple choice</p>
          <h2 class="text-[19px] md:text-[22px] font-semibold text-ink leading-[1.35] tracking-[-0.01em] mb-6">{{ q.q }}</h2>

          <div class="space-y-2.5">
            <button
              v-for="(opt, i) in q.opts"
              :key="i"
              :disabled="submitted"
              :class="`w-full text-left flex items-center gap-3 px-4 py-3.5 rounded-xl border transition-all ${
                submitted && i === q.a ? 'bg-mint-light border-mint text-ink' :
                submitted && selected === i && i !== q.a ? 'bg-pink-light border-pink text-ink' :
                selected === i ? 'bg-blue-light border-blue text-ink' :
                'bg-bg-card border-border text-ink2 hover:border-blue-mid'
              }`"
              @click="selected = i"
            >
              <span
                :class="`w-6 h-6 rounded-full grid place-items-center text-[11px] font-bold shrink-0 ${
                  submitted && i === q.a ? 'bg-mint text-white' :
                  submitted && selected === i && i !== q.a ? 'bg-pink text-white' :
                  selected === i ? 'bg-blue text-white' :
                  'bg-bg-soft text-ink3'
                }`"
              >
                {{ String.fromCharCode(65 + i) }}
              </span>
              <span class="text-[14px] font-medium">{{ opt }}</span>
            </button>
          </div>

          <div v-if="submitted" :class="`mt-6 p-4 rounded-xl border animate-fade-in ${isCorrect ? 'bg-mint-light border-mint/30' : 'bg-amber-50 border-amber-200'}`">
            <div class="flex items-start gap-3">
              <CheckCircle2 v-if="isCorrect" :size="18" class="text-mint mt-0.5 shrink-0" />
              <XCircle v-else :size="18" class="text-amber-600 mt-0.5 shrink-0" />
              <div>
                <p class="text-[13.5px] font-semibold text-ink mb-1">{{ isCorrect ? "Correct" : "Needs improvement" }}</p>
                <p class="text-[13px] text-ink2 leading-[1.6] font-light">
                  {{ isCorrect ? `Solid — you applied the right method for ${priority ?? "this topic"}.` : `The correct answer is ${String.fromCharCode(65 + q.a)}. Review the underlying concept before moving on.` }}
                </p>
                <p class="mt-2 flex items-start gap-1.5 text-[12.5px] text-violet">
                  <Lightbulb :size="13" class="mt-0.5" />
                  <span>AI note: this pattern will feed into your final insight — one question won't change your plan.</span>
                </p>
              </div>
            </div>
          </div>

          <div class="mt-7 flex items-center justify-between gap-3">
            <RouterLink to="/student-plan" class="inline-flex items-center gap-2 text-[13px] text-ink3 font-medium hover:text-ink transition">
              <ArrowLeft :size="14" /> Back to plan
            </RouterLink>
            <button
              v-if="!submitted"
              :disabled="selected === null"
              class="inline-flex items-center justify-center gap-2 bg-blue text-white text-[14px] font-semibold px-6 py-3 rounded-full hover:bg-blue/90 transition disabled:opacity-40 disabled:cursor-not-allowed"
              @click="handleSubmit"
            >
              Submit answer
            </button>
            <button
              v-else
              class="inline-flex items-center justify-center gap-2 bg-blue text-white text-[14px] font-semibold px-6 py-3 rounded-full hover:bg-blue/90 hover:shadow-[0_8px_24px_-6px_rgba(59,110,248,0.35)] transition-all"
              @click="handleNext"
            >
              {{ idx + 1 >= total ? "See my insight" : "Next question" }} <ArrowRight :size="16" />
            </button>
          </div>
        </section>
      </div>
    </main>

    <Footer />
  </div>
</template>
