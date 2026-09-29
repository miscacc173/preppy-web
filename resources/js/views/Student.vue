<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import { Search, Check, AlertCircle, Sparkles, GraduationCap, Target, CalendarDays, ListChecks, BookOpen, ArrowRight } from "lucide-vue-next";
import { YEARS_BY_LEVEL, GRADES_BY_LEVEL, SUBJECTS as UI_SUBJECTS, CONFIDENCE, MONTHS, type StudyLevel, type Confidence } from "./student/data";
import { ApiError } from "@/lib/api";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiCourse, UiEntity, UiSubject, UiTopic } from "@/data/models/platform";
import { boardsForCourse, subjectsForCourse } from "@/data/academicSelection";
import SectionCard from "./student/SectionCard.vue";
import ChoicePill from "./student/ChoicePill.vue";
import InlineError from "./student/InlineError.vue";
import SummaryRow from "./student/SummaryRow.vue";

setPageMeta({
  title: "Student Setup — Preppy",
  description: "Personalise your AI Star Prep learning journey. Tell us what you're studying, your subjects, target grades, and exam timeline.",
});

const router = useRouter();

const level = ref<StudyLevel | "">("");
const courseId = ref<number | null>(null);
const board = ref("");
const year = ref("");
const query = ref("");
const subjects = ref<string[]>([]);
const topicIds = ref<number[]>([]);
const weak = ref<string[]>([]);
const notSure = ref(false);
const confidence = reactive<Record<string, Confidence>>({});
const targets = reactive<Record<string, string>>({});
const examMonth = ref("");
const examYear = ref("");
const errors = reactive<Record<string, string>>({});
const catalogBoards = ref<UiEntity[]>([]);
const catalogCourses = ref<UiCourse[]>([]);
const catalogSubjects = ref<UiSubject[]>([]);
const catalogTopics = ref<UiTopic[]>([]);
const loading = ref(true);
const saving = ref(false);
const serverError = ref("");

const availableLevels = computed(() => {
  return catalogCourses.value.map((course) => ({ id: course.id, value: course.level, label: course.name, status: course.status }));
});
const selectionCatalog = computed(() => ({ examBoards: catalogBoards.value, courses: catalogCourses.value, subjects: catalogSubjects.value, topics: catalogTopics.value, plans: [] }));
const selectedCourse = computed(() => catalogCourses.value.find((course) => course.id === courseId.value) ?? null);
const availableBoards = computed(() => boardsForCourse(selectionCatalog.value, courseId.value));
const availableYears = computed(() => (level.value ? YEARS_BY_LEVEL[level.value] : []));
const availableGrades = computed(() => (level.value ? GRADES_BY_LEVEL[level.value] : []));

const filteredSubjects = computed(() => {
  const source = subjectsForCourse(selectionCatalog.value, courseId.value).map((subject) => ({
    name: subject.name,
    status: subject.status,
    icon: UI_SUBJECTS.find((item) => item.name === subject.name)?.icon ?? BookOpen,
  }));
  return source.filter((item) => item.name.toLowerCase().includes(query.value.trim().toLowerCase()));
});
const selectedSubjectRecords = computed(() => catalogSubjects.value.filter((subject) => subjects.value.includes(subject.name)));
const availableTopics = computed(() => {
  const ids = new Set(selectedSubjectRecords.value.map((subject) => subject.id));
  return catalogTopics.value.filter((topic) => ids.has(topic.subjectId));
});

onMounted(async () => {
  try {
    const [catalog, bundle] = await Promise.all([platformRepository.catalog(), platformRepository.account()]);
    catalogBoards.value = catalog.examBoards;
    catalogCourses.value = catalog.courses;
    catalogSubjects.value = catalog.subjects;
    catalogTopics.value = catalog.topics;
    const account = bundle.account;
    level.value = account.course?.status === "active" ? account.course.level : "";
    courseId.value = account.course?.status === "active" ? account.course.id : null;
    board.value = account.examBoard?.status === "active" ? account.examBoard.name : "";
    year.value = account.schoolYear;
    const activeSubjectNames = new Set(catalog.subjects.filter((item) => item.status === "active").map((item) => item.name));
    subjects.value = account.subjects.map((item) => item.name).filter((name) => activeSubjectNames.has(name));
    weak.value = account.preferences.weakSubjects;
    notSure.value = account.preferences.notSureWeak;
    Object.assign(confidence, account.preferences.confidence);
    Object.assign(targets, account.preferences.targetGrades);
    examMonth.value = account.preferences.examMonth;
    examYear.value = account.preferences.examYear;
    const activeTopicIds = new Set(catalog.topics.filter((item) => item.status === "active").map((item) => item.id));
    topicIds.value = account.preferences.topicIds.filter((id) => activeTopicIds.has(id));
  } catch (error) {
    serverError.value = error instanceof Error ? error.message : "Your academic setup could not be loaded.";
  } finally {
    loading.value = false;
  }
});

function selectCourse(id: number) {
  const course = catalogCourses.value.find((item) => item.id === id);
  if (!course || course.status !== "active") return;
  courseId.value = course.id;
  level.value = course.level;
  board.value = "";
  year.value = "";
  Object.keys(targets).forEach((k) => delete targets[k]);
  subjects.value = [];
  topicIds.value = [];
}

function toggleSubject(name: string) {
  const on = subjects.value.includes(name);
  subjects.value = on ? subjects.value.filter((s) => s !== name) : [...subjects.value, name];
  if (on) {
    weak.value = weak.value.filter((s) => s !== name);
    delete confidence[name];
    delete targets[name];
    const removedSubject = catalogSubjects.value.find((subject) => subject.name === name);
    if (removedSubject) topicIds.value = topicIds.value.filter((id) => catalogTopics.value.find((topic) => topic.id === id)?.subjectId !== removedSubject.id);
  }
}

function toggleTopic(id: number) {
  topicIds.value = topicIds.value.includes(id) ? topicIds.value.filter((value) => value !== id) : [...topicIds.value, id];
}

function toggleWeak(name: string) {
  notSure.value = false;
  weak.value = weak.value.includes(name) ? weak.value.filter((s) => s !== name) : [...weak.value, name];
}

function toggleNotSure() {
  notSure.value = !notSure.value;
  if (notSure.value) weak.value = [];
}

function setConfidenceFor(s: string, id: Confidence) {
  if (confidence[s] === id) delete confidence[s];
  else confidence[s] = id;
}

function setTargetFor(s: string, g: string) {
  if (targets[s] === g) delete targets[s];
  else targets[s] = g;
}

function validate() {
  Object.keys(errors).forEach((k) => delete errors[k]);
  if (!level.value) errors.level = "Please choose your study level.";
  if (!board.value) errors.board = "Please choose your exam board.";
  if (subjects.value.length === 0) errors.subjects = "Select at least one subject.";
  return Object.keys(errors).length === 0;
}

async function submit() {
  if (!validate()) {
    const first = document.querySelector("[data-error='true']");
    if (first) first.scrollIntoView({ behavior: "smooth", block: "center" });
    return;
  }
  serverError.value = "";
  saving.value = true;
  try {
    const selectedBoard = catalogBoards.value.find((item) => item.name === board.value);
    const course = selectedCourse.value;
    const selectedSubjectIds = catalogSubjects.value.filter((item) => subjects.value.includes(item.name)).map((item) => item.id);
    const onboarding = {
      level: level.value, board: board.value, year: year.value, subjects: subjects.value,
      weak: notSure.value ? [] : weak.value, notSureWeak: notSure.value,
      confidence, targets, examMonth: examMonth.value, examYear: examYear.value,
      completedAt: new Date().toISOString(),
    };
    await platformRepository.updateAccount({
      course_id: course?.id ?? null,
      exam_board_id: selectedBoard?.id ?? null,
      school_year: year.value || null,
      subject_ids: selectedSubjectIds,
      preferences: {
        weak_subjects: onboarding.weak,
        not_sure_weak: onboarding.notSureWeak,
        confidence: { ...confidence },
        target_grades: { ...targets },
        exam_month: examMonth.value || null,
        exam_year: examYear.value || null,
        topic_ids: topicIds.value,
      },
    });
    router.push("/student-plan");
  } catch (error) {
    serverError.value = error instanceof ApiError ? error.message : "Your learning setup could not be saved.";
  } finally {
    saving.value = false;
  }
}

const currentYear = new Date().getFullYear();
const examYears = [currentYear, currentYear + 1, currentYear + 2, currentYear + 3];

const targetEntries = computed(() => Object.entries(targets));
const examPeriod = computed(() => (examMonth.value || examYear.value ? `${examMonth.value}${examMonth.value && examYear.value ? " " : ""}${examYear.value}` : "—"));
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <TopNav />

    <main class="flex-1">
      <!-- Welcome -->
      <section class="px-6 pt-14 pb-8 border-b border-border bg-[#FAFBFC]">
        <div class="max-w-[880px] mx-auto text-center">
          <p class="label-mono mb-3 text-blue-600">Student setup</p>
          <h1 class="text-[32px] md:text-[40px] font-bold text-ink tracking-[-0.03em] leading-[1.1] mb-4">
            Let's personalise your <span class="text-blue-600">learning journey</span>
          </h1>
          <p class="text-[15px] text-ink2 leading-[1.65] font-light max-w-[620px] mx-auto">
            Tell us what you're studying and where you need support. AI Star Prep will use your answers to create a more relevant learning experience.
          </p>
        </div>
      </section>

      <div class="max-w-[880px] mx-auto px-5 md:px-6 py-10 md:py-14 space-y-8">
        <div v-if="loading" class="rounded-xl border border-border bg-bg-card p-5 text-center text-[13px] text-ink3">Loading your academic profile…</div>
        <div v-if="serverError" class="rounded-xl border border-pink/30 bg-pink-light p-4 text-[13px] text-pink">{{ serverError }}</div>
        <!-- Step 1: Education details -->
        <SectionCard :icon="GraduationCap" eyebrow="Step 1" title="What are you studying?" subtitle="We'll match the right syllabus and exam board.">
          <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Qualification / Study level</label>
          <div class="flex flex-wrap gap-2">
            <ChoicePill v-for="item in availableLevels" :key="item.id" :active="courseId === item.id" :coming-soon="item.status === 'coming_soon'" @click="selectCourse(item.id)">{{ item.label }}</ChoicePill>
          </div>
          <InlineError :show="!!errors.level" :message="errors.level" />

          <template v-if="level">
            <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2 mt-6">Exam board</label>
            <div class="flex flex-wrap gap-2">
              <ChoicePill v-for="b in availableBoards" :key="b.id" :active="board === b.name" :coming-soon="b.status === 'coming_soon'" @click="board = b.name">{{ b.name }}</ChoicePill>
            </div>
            <InlineError :show="!!errors.board" :message="errors.board" />

            <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2 mt-6">Current year (optional)</label>
            <div class="flex flex-wrap gap-2">
              <ChoicePill v-for="y in availableYears" :key="y" :active="year === y" @click="year = y">{{ y }}</ChoicePill>
            </div>
          </template>
        </SectionCard>

        <!-- Step 2: Subject selection -->
        <SectionCard :icon="BookOpen" eyebrow="Step 2" title="Which subjects are you preparing for?" subtitle="Pick every subject you'd like AI Star Prep to help with.">
          <div class="relative mb-4">
            <Search :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-ink3" />
            <input v-model="query" placeholder="Search subjects" class="w-full bg-bg-soft border border-border rounded-lg pl-10 pr-4 py-2.5 text-[14px] text-ink outline-none focus:border-blue" />
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
            <button
              v-for="s in filteredSubjects"
              :key="s.name"
              :disabled="s.status === 'coming_soon'"
              :class="`group relative flex flex-col items-start gap-2 p-3.5 text-left rounded-xl border transition-all ${
                subjects.includes(s.name) ? 'bg-blue-light border-blue text-blue' : s.status === 'coming_soon' ? 'bg-bg-soft border-border text-ink3 cursor-not-allowed' : 'bg-bg-card border-border text-ink2 hover:border-blue-mid hover:-translate-y-[1px]'
              }`"
              @click="toggleSubject(s.name)"
            >
              <div :class="`w-9 h-9 rounded-lg grid place-items-center ${subjects.includes(s.name) ? 'bg-white/70' : 'bg-bg-soft'}`">
                <component :is="s.icon" :size="17" :class="subjects.includes(s.name) ? 'text-blue' : 'text-ink3'" />
              </div>
              <span class="text-[13px] font-semibold leading-tight">{{ s.name }}</span>
              <span v-if="s.status === 'coming_soon'" class="rounded-full bg-gold-light px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gold">Coming soon</span>
              <span v-if="subjects.includes(s.name)" class="absolute top-2 right-2 w-5 h-5 rounded-full bg-blue text-white grid place-items-center">
                <Check :size="12" :stroke-width="3" />
              </span>
            </button>
            <p v-if="filteredSubjects.length === 0" class="col-span-full text-[13px] text-ink3 py-4">No subjects match "{{ query }}".</p>
          </div>
          <InlineError :show="!!errors.subjects" :message="errors.subjects" />
          <div v-if="subjects.length" class="mt-6 border-t border-border pt-5">
            <p class="text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Preferred topics (optional)</p>
            <p v-if="!availableTopics.length" class="text-[13px] text-ink3">No active topics have been added for these subjects by an administrator yet.</p>
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <button v-for="topic in availableTopics" :key="topic.id" :disabled="topic.status === 'coming_soon'" :class="`flex items-center justify-between gap-2 rounded-lg border px-3 py-2.5 text-left text-[12px] font-medium transition ${topicIds.includes(topic.id) ? 'border-violet bg-violet-light text-violet' : topic.status === 'coming_soon' ? 'border-border bg-bg-soft text-ink3 cursor-not-allowed' : 'border-border bg-bg-card text-ink2 hover:border-violet'}`" @click="toggleTopic(topic.id)">
                <span>{{ topic.name }}</span><span v-if="topic.status === 'coming_soon'" class="rounded-full bg-gold-light px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gold">Coming soon</span>
              </button>
            </div>
          </div>
        </SectionCard>

        <!-- Step 3: Weak subjects -->
        <SectionCard
          v-if="subjects.length > 0"
          :icon="AlertCircle"
          eyebrow="Step 3"
          title="Which subjects need the most attention?"
          subtitle="Pick any you find hardest — this helps us prioritise practice."
        >
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
            <button
              v-for="s in subjects"
              :key="s"
              :disabled="notSure"
              :class="`px-3.5 py-3 text-[13px] font-medium rounded-lg border text-left transition ${
                weak.includes(s) ? 'bg-amber-50 border-amber-400 text-amber-700' : 'bg-bg-card border-border text-ink2 hover:border-blue-mid'
              } ${notSure ? 'opacity-40 cursor-not-allowed' : ''}`"
              @click="toggleWeak(s)"
            >
              <Check v-if="weak.includes(s)" :size="13" class="inline mr-1.5 -mt-0.5" />
              {{ s }}
            </button>
          </div>

          <button
            :class="`mt-4 flex items-start gap-3 w-full text-left p-3.5 rounded-lg border transition ${notSure ? 'bg-blue-light border-blue' : 'bg-bg-soft border-border hover:border-blue-mid'}`"
            @click="toggleNotSure"
          >
            <span :class="`mt-0.5 w-5 h-5 rounded-full border-2 grid place-items-center shrink-0 ${notSure ? 'bg-blue border-blue' : 'border-ink3'}`">
              <Check v-if="notSure" :size="12" class="text-white" :stroke-width="3" />
            </span>
            <span>
              <span :class="`block text-[14px] font-semibold ${notSure ? 'text-blue' : 'text-ink'}`">I'm not sure yet</span>
              <span class="block text-[12.5px] text-ink3 font-light mt-0.5">
                That's okay. AI Star Prep can identify your weak areas through practice and diagnostic sessions.
              </span>
            </span>
          </button>
        </SectionCard>

        <!-- Step 4: Confidence -->
        <SectionCard v-if="subjects.length > 0" :icon="Sparkles" eyebrow="Step 4" title="How confident do you feel? (optional)" subtitle="A quick self-assessment for each subject.">
          <div class="space-y-3">
            <div v-for="s in subjects" :key="s" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3.5 rounded-lg border border-border bg-bg-soft/50">
              <span class="text-[13.5px] font-semibold text-ink">{{ s }}</span>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="c in CONFIDENCE"
                  :key="c.id"
                  :class="`px-3 py-1.5 text-[12px] font-medium rounded-full border transition ${
                    confidence[s] === c.id ? 'bg-blue text-white border-blue' : 'bg-bg-card text-ink2 border-border hover:border-blue-mid'
                  }`"
                  @click="setConfidenceFor(s, c.id)"
                >
                  {{ c.label }}
                </button>
              </div>
            </div>
          </div>
        </SectionCard>

        <!-- Step 5: Target grades -->
        <SectionCard v-if="subjects.length > 0 && level" :icon="Target" eyebrow="Step 5" title="What grades are you aiming for?" subtitle="Set a target for each subject (optional).">
          <div class="space-y-3">
            <div v-for="s in subjects" :key="s" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3.5 rounded-lg border border-border bg-bg-soft/50">
              <span class="text-[13.5px] font-semibold text-ink">{{ s }}</span>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="g in availableGrades"
                  :key="g"
                  :class="`min-w-[42px] px-3 py-1.5 text-[12.5px] font-semibold rounded-full border transition ${
                    targets[s] === g ? 'bg-gold text-ink border-gold' : 'bg-bg-card text-ink2 border-border hover:border-blue-mid'
                  }`"
                  @click="setTargetFor(s, g)"
                >
                  {{ g }}
                </button>
              </div>
            </div>
          </div>
        </SectionCard>

        <!-- Step 6: Exam timeline -->
        <SectionCard :icon="CalendarDays" eyebrow="Step 6" title="When are your exams?" subtitle="An approximate month and year is enough.">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Exam month</label>
              <select v-model="examMonth" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-blue">
                <option value="">Select month</option>
                <option v-for="m in MONTHS" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
            <div>
              <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Exam year</label>
              <select v-model="examYear" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-blue">
                <option value="">Select year</option>
                <option v-for="y in examYears" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
          </div>
        </SectionCard>

        <!-- Summary -->
        <SectionCard :icon="ListChecks" eyebrow="Review" title="Your learning setup" subtitle="Check everything looks right before we build your plan.">
          <dl class="divide-y divide-border">
            <SummaryRow label="Study level">{{ level || "—" }}</SummaryRow>
            <SummaryRow label="Exam board">{{ board || "—" }}</SummaryRow>
            <SummaryRow v-if="year" label="Current year">{{ year }}</SummaryRow>
            <SummaryRow label="Selected subjects">{{ subjects.length ? subjects.join(", ") : "—" }}</SummaryRow>
            <SummaryRow label="Needs most attention">
              {{ notSure ? "Not sure yet — AI will identify" : weak.length ? weak.join(", ") : "—" }}
            </SummaryRow>
            <SummaryRow v-if="targetEntries.length > 0" label="Target grades">
              <div class="flex flex-col gap-0.5">
                <span v-for="[s, g] in targetEntries" :key="s">{{ s }} — <span class="font-semibold text-ink">{{ g }}</span></span>
              </div>
            </SummaryRow>
            <SummaryRow label="Exam period">{{ examPeriod }}</SummaryRow>
          </dl>
        </SectionCard>

        <!-- Primary action -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-2">
          <p class="text-[12.5px] text-ink3 font-light">You can update these preferences anytime from Settings.</p>
          <button
            class="inline-flex items-center justify-center gap-2 bg-blue text-white text-[14px] font-semibold px-6 py-3 rounded-full hover:bg-blue/90 hover:shadow-[0_8px_24px_-6px_rgba(59,110,248,0.35)] transition-all"
            :disabled="saving || loading"
            @click="submit"
          >
            {{ saving ? "Saving…" : "Create my learning plan" }} <ArrowRight :size="16" />
          </button>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
