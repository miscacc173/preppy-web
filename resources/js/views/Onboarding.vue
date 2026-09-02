<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import Logo from "@/components/brand/Logo.vue";
import { ArrowRight, ArrowLeft, Check } from "lucide-vue-next";
import Choices from "./onboarding/Choices.vue";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiCatalog } from "@/data/models/platform";
import { activeCourses, boardsForCourse, subjectsForCourse } from "@/data/academicSelection";

setPageMeta({ title: "Onboarding — AiStar Prep" });

const GOALS = ["Hit A*", "Lock in an A", "Pass confidently", "Resit / improve grade"];
const YEARS = ["Year 10", "Year 11", "Year 12 (AS)", "Year 13 (A2)"];

const router = useRouter();
const step = ref(0);
const data = reactive({ year: "", qual: "", board: "", subjects: [] as string[], goal: "" });
const catalog = ref<UiCatalog | null>(null);
const loading = ref(true);
const saving = ref(false);
const error = ref("");
const hydrating = ref(true);
const QUALS = computed(() => {
  return activeCourses(catalog.value).map((course) => ({ value: String(course.id), label: course.name, comingSoon: course.status !== "active" }));
});
const selectedCourseId = computed(() => Number(data.qual) || null);
const BOARDS = computed(() => boardsForCourse(catalog.value, selectedCourseId.value).map((board) => ({ value: String(board.id), label: board.name, comingSoon: board.status !== "active" })));
const SUBJECTS = computed(() => subjectsForCourse(catalog.value, selectedCourseId.value));

watch(() => data.qual, () => { if (!hydrating.value) { data.board = ""; data.subjects = []; } });

const steps = ["Qualification", "Exam board", "Year", "Subjects", "Goal"];
const progress = computed(() => ((step.value + 1) / steps.length) * 100);

async function next() {
  if (step.value < steps.length - 1) step.value += 1;
  else {
    saving.value = true;
    error.value = "";
    try {
      const board = BOARDS.value.find((item) => item.value === data.board && !item.comingSoon);
      const course = catalog.value?.courses.find((item) => item.status === "active" && item.id === selectedCourseId.value);
      const subjectIds = SUBJECTS.value.filter((subject) => subject.status === "active" && data.subjects.includes(subject.name)).map((subject) => subject.id);
      await platformRepository.updateAccount({
        school_year: data.year,
        exam_board_id: board ? Number(board.value) : null,
        course_id: course?.id ?? null,
        subject_ids: subjectIds,
        preferences: { goal: data.goal },
      });
      await router.push("/pricing");
    } catch (reason) {
      error.value = reason instanceof Error ? reason.message : "Your onboarding details could not be saved.";
    } finally {
      saving.value = false;
    }
  }
}
function back() {
  if (step.value > 0) step.value -= 1;
}

function toggleSubject(s: string) {
  data.subjects = data.subjects.includes(s) ? data.subjects.filter((x) => x !== s) : [...data.subjects, s];
}

const canNext = computed(
  () =>
    (step.value === 0 && !!data.qual) ||
    (step.value === 1 && !!data.board) ||
    (step.value === 2 && !!data.year) ||
    (step.value === 3 && data.subjects.length > 0) ||
    (step.value === 4 && !!data.goal),
);

onMounted(async () => {
  try {
    const [catalogResponse, accountResponse] = await Promise.all([platformRepository.catalog(), platformRepository.account()]);
    catalog.value = catalogResponse;
    const account = accountResponse.account;
    data.year = account.schoolYear;
    data.qual = account.course?.status === "active" ? String(account.course.id) : "";
    data.board = account.examBoard?.status === "active" ? String(account.examBoard.id) : "";
    const activeSubjects = new Set(catalogResponse.subjects.filter((subject) => subject.status === "active").map((subject) => subject.name));
    data.subjects = account.subjects.map((subject) => subject.name).filter((name) => activeSubjects.has(name));
    data.goal = account.preferences.goal;
    await nextTick();
    hydrating.value = false;
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Onboarding data could not be loaded.";
  } finally {
    hydrating.value = false;
    loading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <div class="px-6 py-5 border-b border-border bg-bg-card"><Logo /></div>
    <div class="flex-1 max-w-[600px] w-full mx-auto px-5 py-10">
      <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
          <p class="label-mono">Onboarding · {{ steps[step] }}</p>
          <p class="font-mono text-[11px] text-ink3">{{ step + 1 }} / {{ steps.length }}</p>
        </div>
        <div class="h-1 bg-bg-soft2 rounded-full overflow-hidden">
          <div class="h-full bg-gold rounded-full transition-all duration-500" :style="{ width: `${progress}%` }" />
        </div>
      </div>

      <div :key="step" class="bg-bg-card border border-border rounded-2xl p-7 reveal-up">
        <p v-if="loading" class="mb-5 text-[13px] text-ink3">Loading available academic options…</p>
        <p v-if="error" class="mb-5 rounded-lg bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>
        <Choices v-if="step === 0" v-model="data.qual" title="Qualification" sub="Choose the qualification or course you are preparing for." :items="QUALS" />
        <Choices v-if="step === 1" v-model="data.board" title="Exam board" sub="Only boards configured for your qualification are shown." :items="BOARDS" />
        <Choices v-if="step === 2" v-model="data.year" title="School year" sub="So we can match the syllabus." :items="YEARS" />

        <template v-if="step === 3">
          <h2 class="text-[24px] font-bold text-ink mb-1.5">Your subjects</h2>
          <p class="text-[13.5px] text-ink3 mb-6 font-light">Pick all you want to train. ({{ data.subjects.length }} selected)</p>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5">
            <button
              v-for="s in SUBJECTS"
              :key="s.id"
              :disabled="s.status === 'coming_soon'"
              :class="`px-3 py-3 text-[13px] font-medium rounded-lg border text-left transition ${
                data.subjects.includes(s.name) ? 'bg-blue-light border-blue text-blue' : s.status === 'coming_soon' ? 'bg-bg-soft border-border text-ink3 cursor-not-allowed' : 'bg-bg-card border-border text-ink2 hover:border-blue-mid'
              }`"
              @click="toggleSubject(s.name)"
            >
              <Check v-if="data.subjects.includes(s.name)" :size="13" class="inline mr-1.5 -mt-0.5" /> {{ s.name }}
              <span v-if="s.status === 'coming_soon'" class="ml-1 rounded-full bg-gold-light px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gold">Coming soon</span>
            </button>
          </div>
        </template>

        <Choices v-if="step === 4" v-model="data.goal" title="Your goal" sub="We'll calibrate the training intensity." :items="GOALS" />

        <div class="flex justify-between items-center mt-8">
          <button
            :disabled="step === 0"
            class="inline-flex items-center gap-1.5 text-[13px] text-ink3 hover:text-ink disabled:opacity-30 disabled:cursor-not-allowed"
            @click="back"
          >
            <ArrowLeft :size="14" /> Back
          </button>
          <button
            :disabled="!canNext || loading || saving"
            class="inline-flex items-center gap-2 bg-blue text-white text-[14px] font-semibold px-6 py-2.5 rounded-md hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="next"
          >
            {{ saving ? "Saving…" : step === steps.length - 1 ? "Continue to plans" : "Continue" }} <ArrowRight :size="15" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
