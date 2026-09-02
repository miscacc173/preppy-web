<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { setPageMeta } from "@/lib/useRouteHead";
import { ArrowLeft, Wand2 } from "lucide-vue-next";
import { Field, TextInput, NumberInput, Select, MultiChips, Toggle, FormCard, GenerationOverlay, StepHeader } from "@/components/teacher/creation-ui";
import {
  readOnboarding, readAssessmentDraft, writeAssessmentDraft, saveAssessment, generateAssessmentQuestions, uid,
  type QuestionType, type Assessment,
} from "@/lib/teacherStore";
import { useCatalogOptions } from "@/data/composables/useCatalogOptions";

setPageMeta({ title: "New Assessment — Teacher Workspace" });

const { qualifications: QUALS, boards: BOARDS, subjects: SUBJECTS } = useCatalogOptions();
const QTYPES: QuestionType[] = ["Multiple Choice", "Short Answer", "Structured", "Extended Response", "Data Analysis"];
const DIFFICULTIES = ["Easy", "Medium", "Hard", "Mixed"];
const SOURCES = ["AI Generated", "Question Bank", "Past Papers", "Mixed"];

const router = useRouter();
const route = useRoute();
const onboarding = readOnboarding();
const existing = readAssessmentDraft();

type AssessmentForm = Required<Pick<Assessment, "title" | "subject" | "qualification" | "board" | "className" | "topics" | "questionSource" | "count" | "qtypes" | "difficulty" | "totalMarks" | "duration" | "timed" | "dueDate" | "aiFeedback">> & Partial<Assessment>;

function buildDefaults(): AssessmentForm {
  const cls = onboarding.firstClass ?? undefined;
  const subjectParam = typeof route.query.subject === "string" ? route.query.subject : undefined;
  return {
    title: "",
    subject: subjectParam || cls?.subject || onboarding.subjects?.[0] || "",
    qualification: cls?.qualification || onboarding.quals?.[0] || "GCSE",
    board: cls?.board || onboarding.boards?.[0] || "AQA",
    className: cls?.name || "",
    topics: [],
    questionSource: "AI Generated",
    count: 8,
    qtypes: ["Multiple Choice", "Short Answer"],
    difficulty: "Medium",
    totalMarks: 40,
    duration: 45,
    timed: true,
    dueDate: "",
    aiFeedback: true,
    ...existing,
  };
}

const form = reactive<AssessmentForm>(buildDefaults());
const topicInput = ref("");
const generating = ref(false);
const errors = reactive<Record<string, string>>({});

watch(form, () => writeAssessmentDraft(form), { deep: true });

function addTopic() {
  const t = topicInput.value.trim();
  if (!t) return;
  if (!form.topics?.includes(t)) form.topics = [...(form.topics || []), t];
  topicInput.value = "";
}
function removeTopic(t: string) {
  form.topics = (form.topics || []).filter((x) => x !== t);
}

function validate() {
  Object.keys(errors).forEach((k) => delete errors[k]);
  if (!form.title?.trim()) errors.title = "Give the assessment a title.";
  if (!form.subject) errors.subject = "Select a subject.";
  if (!form.qualification) errors.qualification = "Select a qualification.";
  if (!form.board) errors.board = "Select an exam board.";
  if (!form.topics?.length) errors.topics = "Add at least one topic.";
  if (!form.qtypes?.length) errors.qtypes = "Choose at least one question type.";
  if (!form.count || form.count < 1) errors.count = "Set the number of questions.";
  return Object.keys(errors).length === 0;
}

async function generate() {
  if (!validate()) {
    toast.error("Please complete the required fields.");
    return;
  }
  generating.value = true;
  await new Promise((r) => setTimeout(r, 1800));
  const now = new Date().toISOString();
  const a: Assessment = {
    id: uid(),
    title: form.title!,
    subject: form.subject!,
    qualification: form.qualification!,
    board: form.board!,
    className: form.className || "",
    topics: form.topics!,
    questionSource: (form.questionSource as Assessment["questionSource"]) || "AI Generated",
    count: form.count!,
    qtypes: form.qtypes as QuestionType[],
    difficulty: (form.difficulty as Assessment["difficulty"]) || "Medium",
    totalMarks: form.totalMarks || 40,
    duration: form.duration || 45,
    timed: !!form.timed,
    dueDate: form.dueDate || "",
    aiFeedback: !!form.aiFeedback,
    status: "draft",
    questions: [],
    createdAt: now,
    updatedAt: now,
  };
  a.questions = generateAssessmentQuestions(a);
  saveAssessment(a);
  writeAssessmentDraft(null);
  generating.value = false;
  toast.success("Assessment generated");
  router.push(`/teacher/workspace/assessments/${a.id}`);
}
</script>

<template>
  <button class="inline-flex items-center gap-1.5 text-[13px] text-ink3 hover:text-violet-600 mb-3" @click="router.push('/teacher/workspace/assessments')">
    <ArrowLeft :size="14" /> Back to Assessments
  </button>
  <StepHeader eyebrow="Create Assessment" title="Configure your assessment" subtitle="We've pre-filled details from your teacher setup — change anything that doesn't fit this test." />

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <FormCard title="Assessment details">
      <Field label="Assessment title" :hint="errors.title">
        <TextInput v-model="form.title" placeholder="e.g. Y10 Algebra Diagnostic" />
      </Field>
      <div class="grid grid-cols-2 gap-3">
        <Field label="Subject" :hint="errors.subject">
          <Select v-model="form.subject" :options="SUBJECTS" placeholder="Select subject" />
        </Field>
        <Field label="Class / group">
          <TextInput v-model="form.className" placeholder="e.g. 10A Mathematics" />
        </Field>
        <Field label="Qualification" :hint="errors.qualification">
          <Select v-model="form.qualification" :options="QUALS" />
        </Field>
        <Field label="Exam board" :hint="errors.board">
          <Select v-model="form.board" :options="BOARDS" />
        </Field>
      </div>
    </FormCard>

    <FormCard title="Topics & question bank">
      <Field label="Topics covered" :hint="errors.topics">
        <div class="flex gap-2">
          <TextInput v-model="topicInput" placeholder="Add a topic and press Enter" @keydown.enter.prevent="addTopic" />
          <button class="shrink-0 px-3 rounded-lg bg-bg-soft border border-border text-[12.5px] font-semibold text-ink2 hover:border-violet-300" @click="addTopic">Add</button>
        </div>
        <div v-if="form.topics?.length" class="flex flex-wrap gap-1.5 mt-2">
          <span v-for="t in form.topics" :key="t" class="inline-flex items-center gap-1 text-[11.5px] font-semibold px-2.5 py-1 rounded-full bg-violet-50 text-violet-700">
            {{ t }}
            <button class="text-violet-500 hover:text-violet-700" @click="removeTopic(t)">×</button>
          </span>
        </div>
      </Field>
      <Field label="Question source">
        <Select v-model="form.questionSource" :options="SOURCES" />
      </Field>
    </FormCard>

    <FormCard title="Question structure">
      <Field label="Question types" :hint="errors.qtypes">
        <MultiChips v-model="form.qtypes" :options="QTYPES" />
      </Field>
      <div class="grid grid-cols-3 gap-3">
        <Field label="Questions" :hint="errors.count">
          <NumberInput v-model="form.count" min="1" max="40" />
        </Field>
        <Field label="Total marks">
          <NumberInput v-model="form.totalMarks" min="1" />
        </Field>
        <Field label="Difficulty">
          <Select v-model="form.difficulty" :options="DIFFICULTIES" />
        </Field>
      </div>
    </FormCard>

    <FormCard title="Delivery">
      <div class="grid grid-cols-2 gap-3">
        <Field label="Estimated duration (min)">
          <NumberInput v-model="form.duration" min="5" />
        </Field>
        <Field label="Due date">
          <TextInput v-model="form.dueDate" type="date" />
        </Field>
      </div>
      <Toggle v-model="form.timed" label="Timed mode" desc="Enforce the estimated duration when students take it." />
      <Toggle v-model="form.aiFeedback" label="AI feedback for students" desc="Students see per-question guidance after submission." />
    </FormCard>
  </div>

  <div class="sticky bottom-4 mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 justify-end bg-bg-card/90 backdrop-blur border border-border rounded-full px-4 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.08)]">
    <button class="text-[13px] font-semibold text-ink2 hover:text-ink px-4 py-2 rounded-full" @click="router.push('/teacher/workspace/assessments')">Cancel</button>
    <button class="inline-flex items-center justify-center gap-2 bg-violet-600 text-white text-[13.5px] font-semibold px-5 py-2.5 rounded-full hover:bg-violet-700 transition" @click="generate">
      <Wand2 :size="15" /> Generate Assessment
    </button>
  </div>

  <GenerationOverlay
    :active="generating"
    :messages="[
      'Building your assessment…',
      'Selecting questions for the chosen topics…',
      'Aligning with your qualification and exam board…',
      'Preparing the assessment preview…',
    ]"
  />
</template>
