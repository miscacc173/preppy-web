<script setup lang="ts">
/**
 * Ported from teacher.index.tsx. Reuses the `SectionCard` / `ChoicePill` /
 * `InlineError` / `SummaryRow` helpers and the `SUBJECTS` list already built
 * for `views/student/` (student.tsx) since both onboarding wizards share the
 * exact same building blocks and subject list in the source.
 */
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import TopNav from "@/components/site/TopNav.vue";
import Footer from "@/components/site/Footer.vue";
import {
  Search, Check, ArrowRight, GraduationCap, Users, School, BookOpen, Sparkles, UserSquare2, ListChecks,
} from "lucide-vue-next";
import { SUBJECTS } from "./student/data";
import SectionCard from "./student/SectionCard.vue";
import ChoicePill from "./student/ChoicePill.vue";
import InlineError from "./student/InlineError.vue";
import SummaryRow from "./student/SummaryRow.vue";
import { YEARS_BY_QUAL, ROLES, PRIORITIES, type Qualification } from "./teacher/onboarding/data";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiSubject } from "@/data/models/platform";

setPageMeta({
  title: "Teacher Setup — Preppy",
  description: "Set up your AI Star Prep teaching workspace. Build exam-board-aligned assessments, learning activities and student insights in minutes.",
});

const router = useRouter();

const displayName = ref("");
const school = ref("");
const role = ref("");

interface CatalogOption { name: string; status: string }
const qualifications = ref<CatalogOption[]>([]);
const catalogSubjects = ref<UiSubject[]>([]);
const boardsByQualification = ref<Record<string, CatalogOption[]>>({});
const quals = ref<string[]>([]);
const boards = ref<string[]>([]);

const query = ref("");
const subjects = ref<string[]>([]);
const priorities = ref<string[]>([]);

const className = ref("");
const classQual = ref("");
const classBoard = ref("");
const classSubject = ref("");
const classYear = ref("");
const classSize = ref("");
const skipClass = ref(false);

const errors = reactive<Record<string, string>>({});
const catalogError = ref("");

onMounted(async () => {
  try {
    const catalog = await platformRepository.catalog();
    const levels = Array.from(new Set(catalog.courses.map((course) => course.level).filter(Boolean)));
    qualifications.value = levels.map((name) => ({ name, status: catalog.courses.some((course) => course.level === name && course.status === "active") ? "active" : "coming_soon" }));
    catalogSubjects.value = catalog.subjects;
    const boardRecords = new Map(catalog.examBoards.map((board) => [board.id, board]));
    for (const course of catalog.courses) {
      if (!course.level || course.examBoardId === null) continue;
      const board = boardRecords.get(course.examBoardId);
      if (!board) continue;
      const option = { name: board.name, status: board.status === "active" && course.status === "active" ? "active" : "coming_soon" };
      const current = boardsByQualification.value[course.level] ?? [];
      const previous = current.find((item) => item.name === option.name);
      boardsByQualification.value[course.level] = previous
        ? current.map((item) => item.name === option.name && option.status === "active" ? option : item)
        : [...current, option];
    }
  } catch (reason) {
    catalogError.value = reason instanceof Error ? reason.message : "Academic catalog could not be loaded.";
  }
});

function toggle<T>(arr: T[], v: T): T[] {
  return arr.includes(v) ? arr.filter((x) => x !== v) : [...arr, v];
}

const availableBoards = computed(() => {
  const options = new Map<string, CatalogOption>();
  quals.value.forEach((q) => (boardsByQualification.value[q] ?? []).forEach((item) => {
    const previous = options.get(item.name);
    if (!previous || item.status === "active") options.set(item.name, item);
  }));
  return Array.from(options.values());
});

const classYears = computed(() => (classQual.value ? (YEARS_BY_QUAL[classQual.value as Qualification] ?? []) : []));
const classBoards = computed(() => (classQual.value ? (boardsByQualification.value[classQual.value] ?? []) : []));

const filteredSubjects = computed(() => catalogSubjects.value.map((subject) => ({ ...subject, icon: SUBJECTS.find((item) => item.name === subject.name)?.icon ?? BookOpen })).filter((s) => s.name.toLowerCase().includes(query.value.trim().toLowerCase())));

function toggleQual(q: string) {
  const next = toggle(quals.value, q);
  const stillOk = new Set(next.flatMap((qq) => boardsByQualification.value[qq] ?? []).filter((item) => item.status === "active").map((item) => item.name));
  boards.value = boards.value.filter((x) => stillOk.has(x));
  quals.value = next;
}

function onClassQualChange(v: string) {
  classQual.value = v;
  classBoard.value = "";
  classYear.value = "";
}

function validate() {
  Object.keys(errors).forEach((k) => delete errors[k]);
  if (!displayName.value.trim()) errors.name = "Please enter your teacher display name.";
  if (!role.value) errors.role = "Choose your teaching role.";
  if (quals.value.length === 0) errors.quals = "Select at least one qualification.";
  if (boards.value.length === 0) errors.boards = "Select at least one exam board.";
  if (subjects.value.length === 0) errors.subjects = "Select at least one subject.";
  if (priorities.value.length === 0) errors.priorities = "Pick at least one way you'd like to use AI Star Prep.";
  return Object.keys(errors).length === 0;
}

function submit() {
  if (!validate()) {
    const first = document.querySelector("[data-error='true']");
    if (first) first.scrollIntoView({ behavior: "smooth", block: "center" });
    return;
  }
  try {
    localStorage.setItem(
      "aistar:teacherOnboarding",
      JSON.stringify({
        displayName: displayName.value,
        school: school.value,
        role: role.value,
        quals: quals.value,
        boards: boards.value,
        subjects: subjects.value,
        priorities: priorities.value,
        firstClass: skipClass.value
          ? null
          : { name: className.value, qualification: classQual.value, board: classBoard.value, subject: classSubject.value, year: classYear.value, size: classSize.value },
        completedAt: new Date().toISOString(),
      }),
    );
  } catch {
    /* ignore */
  }
  router.push("/teacher/workspace");
}

const priorityLabels = computed(() => priorities.value.map((id) => PRIORITIES.find((p) => p.id === id)?.label).filter(Boolean));

const firstClassSummary = computed(() => {
  if (skipClass.value || !className.value) return "Skipped — add later from your workspace";
  return `${className.value}${classSubject.value ? ` · ${classSubject.value}` : ""}${classYear.value ? ` · ${classYear.value}` : ""}`;
});

const classSubjectOptions = computed(() => subjects.value.length ? subjects.value : catalogSubjects.value.filter((subject) => subject.status === "active").map((subject) => subject.name));
</script>

<template>
  <div class="min-h-screen bg-bg flex flex-col">
    <TopNav />

    <main class="flex-1">
      <section class="px-6 pt-14 pb-8 border-b border-border bg-[#FAFBFC]">
        <div class="max-w-[880px] mx-auto text-center">
          <p class="label-mono mb-3 text-violet-600">Teacher setup</p>
          <h1 class="text-[32px] md:text-[40px] font-bold text-ink tracking-[-0.03em] leading-[1.1] mb-4">
            Set up your <span class="text-violet-600">teaching workspace</span>
          </h1>
          <p class="text-[15px] text-ink2 leading-[1.65] font-light max-w-[620px] mx-auto">
            Tell us what you teach so AI Star Prep can personalise your assessment tools, learning resources and student insights.
          </p>
        </div>
      </section>

      <div class="max-w-[880px] mx-auto px-5 md:px-6 py-10 md:py-14 space-y-8">
        <p v-if="catalogError" class="rounded-xl bg-pink-light border border-pink/30 p-4 text-[13px] text-pink">{{ catalogError }}</p>
        <!-- Step 1: Profile -->
        <SectionCard :icon="UserSquare2" eyebrow="Step 1" title="Tell us about your teaching" subtitle="A few basics so we can address you correctly.">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Teacher display name</label>
              <input v-model="displayName" placeholder="e.g. Ms Rahman" maxlength="80" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500" />
              <InlineError :show="!!errors.name" :message="errors.name" />
            </div>
            <div>
              <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">School or institution (optional)</label>
              <input v-model="school" placeholder="e.g. Oakwood Grammar" maxlength="120" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500" />
            </div>
          </div>

          <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2 mt-5">Teaching role</label>
          <div class="flex flex-wrap gap-2">
            <ChoicePill v-for="r in ROLES" :key="r" :active="role === r" @click="role = r">{{ r }}</ChoicePill>
          </div>
          <InlineError :show="!!errors.role" :message="errors.role" />
        </SectionCard>

        <!-- Step 2: Qualifications & Boards -->
        <SectionCard :icon="GraduationCap" eyebrow="Step 2" title="Which qualifications and exam boards do you teach?" subtitle="Select every option that applies — you can teach more than one.">
          <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Qualifications</label>
          <div class="flex flex-wrap gap-2">
            <ChoicePill v-for="q in qualifications" :key="q.name" :active="quals.includes(q.name)" :coming-soon="q.status === 'coming_soon'" @click="toggleQual(q.name)">{{ q.name }}</ChoicePill>
          </div>
          <InlineError :show="!!errors.quals" :message="errors.quals" />

          <template v-if="quals.length > 0">
            <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2 mt-6">Exam boards</label>
            <div class="flex flex-wrap gap-2">
              <ChoicePill v-for="b in availableBoards" :key="b.name" :active="boards.includes(b.name)" :coming-soon="b.status === 'coming_soon'" @click="boards = toggle(boards, b.name)">{{ b.name }}</ChoicePill>
            </div>
            <InlineError :show="!!errors.boards" :message="errors.boards" />
          </template>
        </SectionCard>

        <!-- Step 3: Subjects -->
        <SectionCard :icon="BookOpen" eyebrow="Step 3" title="Which subjects do you teach?" subtitle="Pick every subject you want to prepare tools for.">
          <div class="relative mb-4">
            <Search :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-ink3" />
            <input v-model="query" placeholder="Search subjects" class="w-full bg-bg-soft border border-border rounded-lg pl-10 pr-4 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500" />
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
            <button
              v-for="s in filteredSubjects"
              :key="s.name"
              :disabled="s.status === 'coming_soon'"
              :class="`group relative flex flex-col items-start gap-2 p-3.5 text-left rounded-xl border transition-all ${
                subjects.includes(s.name) ? 'bg-violet-50 border-violet-500 text-violet-700' : s.status === 'coming_soon' ? 'bg-bg-soft border-border text-ink3 cursor-not-allowed' : 'bg-bg-card border-border text-ink2 hover:border-violet-300 hover:-translate-y-[1px]'
              }`"
              @click="subjects = toggle(subjects, s.name)"
            >
              <div :class="`w-9 h-9 rounded-lg grid place-items-center ${subjects.includes(s.name) ? 'bg-white/70' : 'bg-bg-soft'}`">
                <component :is="s.icon" :size="17" :class="subjects.includes(s.name) ? 'text-violet-600' : 'text-ink3'" />
              </div>
              <span class="text-[13px] font-semibold leading-tight">{{ s.name }}</span>
              <span v-if="s.status === 'coming_soon'" class="rounded-full bg-gold-light px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gold">Coming soon</span>
              <span v-if="subjects.includes(s.name)" class="absolute top-2 right-2 w-5 h-5 rounded-full bg-violet-600 text-white grid place-items-center">
                <Check :size="12" :stroke-width="3" />
              </span>
            </button>
            <p v-if="filteredSubjects.length === 0" class="col-span-full text-[13px] text-ink3 py-4">No subjects match "{{ query }}".</p>
          </div>
          <InlineError :show="!!errors.subjects" :message="errors.subjects" />
        </SectionCard>

        <!-- Step 4: Priorities -->
        <SectionCard :icon="Sparkles" eyebrow="Step 4" title="How would you like to use AI Star Prep?" subtitle="Pick every goal that matches your workflow.">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <button
              v-for="p in PRIORITIES"
              :key="p.id"
              :class="`flex items-start gap-3 p-3.5 text-left rounded-xl border transition ${priorities.includes(p.id) ? 'bg-violet-50 border-violet-500' : 'bg-bg-card border-border hover:border-violet-300'}`"
              @click="priorities = toggle(priorities, p.id)"
            >
              <div :class="`w-9 h-9 rounded-lg grid place-items-center shrink-0 ${priorities.includes(p.id) ? 'bg-white/70' : 'bg-bg-soft'}`">
                <component :is="p.icon" :size="17" :class="priorities.includes(p.id) ? 'text-violet-600' : 'text-ink3'" />
              </div>
              <div class="min-w-0">
                <div class="text-[13.5px] font-semibold text-ink leading-tight">{{ p.label }}</div>
                <div class="text-[12.5px] text-ink3 font-light mt-0.5">{{ p.desc }}</div>
              </div>
              <span v-if="priorities.includes(p.id)" class="ml-auto mt-0.5 w-5 h-5 rounded-full bg-violet-600 text-white grid place-items-center shrink-0">
                <Check :size="12" :stroke-width="3" />
              </span>
            </button>
          </div>
          <InlineError :show="!!errors.priorities" :message="errors.priorities" />
        </SectionCard>

        <!-- Step 5: First class (optional) -->
        <SectionCard :icon="School" eyebrow="Step 5 · Optional" title="Set up your first class" subtitle="You can also skip and create classes later from your workspace.">
          <button
            :class="`mb-4 flex items-center gap-2.5 w-full sm:w-auto text-left px-3.5 py-2.5 rounded-lg border transition ${skipClass ? 'bg-violet-50 border-violet-500' : 'bg-bg-soft border-border hover:border-violet-300'}`"
            @click="skipClass = !skipClass"
          >
            <span :class="`w-5 h-5 rounded-full border-2 grid place-items-center shrink-0 ${skipClass ? 'bg-violet-600 border-violet-600' : 'border-ink3'}`">
              <Check v-if="skipClass" :size="12" class="text-white" :stroke-width="3" />
            </span>
            <span :class="`text-[13.5px] font-semibold ${skipClass ? 'text-violet-700' : 'text-ink'}`">Skip for now</span>
          </button>

          <div :class="skipClass ? 'opacity-40 pointer-events-none' : ''">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Class name</label>
                <input v-model="className" placeholder="e.g. A Level Physics — Year 12" maxlength="80" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500" />
              </div>
              <div>
                <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Qualification</label>
                <select :value="classQual" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500" @change="onClassQualChange(($event.target as HTMLSelectElement).value)">
                  <option value="">Select qualification</option>
                  <option v-for="q in qualifications" :key="q.name" :value="q.name" :disabled="q.status === 'coming_soon'">{{ q.name }}{{ q.status === 'coming_soon' ? ' — Coming soon' : '' }}</option>
                </select>
              </div>
              <div>
                <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Exam board</label>
                <select v-model="classBoard" :disabled="!classQual" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500 disabled:opacity-60">
                  <option value="">Select exam board</option>
                  <option v-for="b in classBoards" :key="b.name" :value="b.name" :disabled="b.status === 'coming_soon'">{{ b.name }}{{ b.status === 'coming_soon' ? ' — Coming soon' : '' }}</option>
                </select>
              </div>
              <div>
                <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Subject</label>
                <select v-model="classSubject" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500">
                  <option value="">Select subject</option>
                  <option v-for="s in classSubjectOptions" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
              <div>
                <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Year or level</label>
                <select v-model="classYear" :disabled="!classQual" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500 disabled:opacity-60">
                  <option value="">Select year</option>
                  <option v-for="y in classYears" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div>
                <label class="block text-[12px] font-semibold uppercase tracking-wider text-ink3 mb-2">Estimated students</label>
                <input v-model="classSize" type="number" min="1" max="200" placeholder="e.g. 24" class="w-full bg-bg-soft border border-border rounded-lg px-3.5 py-2.5 text-[14px] text-ink outline-none focus:border-violet-500" />
              </div>
            </div>
          </div>
        </SectionCard>

        <!-- Summary -->
        <SectionCard :icon="ListChecks" eyebrow="Review" title="Your teacher setup" subtitle="Check everything looks right before we open your workspace.">
          <dl class="divide-y divide-border">
            <SummaryRow label="Display name">{{ displayName || "—" }}</SummaryRow>
            <SummaryRow v-if="school" label="School">{{ school }}</SummaryRow>
            <SummaryRow label="Role">{{ role || "—" }}</SummaryRow>
            <SummaryRow label="Qualifications">{{ quals.length ? quals.join(", ") : "—" }}</SummaryRow>
            <SummaryRow label="Exam boards">{{ boards.length ? boards.join(", ") : "—" }}</SummaryRow>
            <SummaryRow label="Subjects">{{ subjects.length ? subjects.join(", ") : "—" }}</SummaryRow>
            <SummaryRow label="Primary goals">{{ priorityLabels.length ? priorityLabels.join(", ") : "—" }}</SummaryRow>
            <SummaryRow label="First class">{{ firstClassSummary }}</SummaryRow>
          </dl>
        </SectionCard>

        <!-- Primary action -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-2">
          <p class="text-[12.5px] text-ink3 font-light flex items-center gap-1.5">
            <Users :size="13" /> You can update these preferences anytime from Settings.
          </p>
          <button
            class="inline-flex items-center justify-center gap-2 bg-violet-600 text-white text-[14px] font-semibold px-6 py-3 rounded-full hover:bg-violet-700 hover:shadow-[0_8px_24px_-6px_rgba(124,58,237,0.35)] transition-all"
            @click="submit"
          >
            Create my teacher workspace <ArrowRight :size="16" />
          </button>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>
