<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { setPageMeta } from "@/lib/useRouteHead";
import { ArrowLeft, Target } from "lucide-vue-next";
import { Field, TextInput, NumberInput, Select, Toggle, FormCard, StepHeader } from "@/components/teacher/creation-ui";
import { readOnboarding, readPracticeDraft, writePracticeDraft, savePractice, uid, type Practice } from "@/lib/teacherStore";
import SuccessScreen from "./SuccessScreen.vue";
import Row from "./Row.vue";
import { useCatalogOptions } from "@/data/composables/useCatalogOptions";

setPageMeta({ title: "Assign Practice — Teacher Workspace" });

const { qualifications: QUALS, boards: BOARDS, subjects: SUBJECTS } = useCatalogOptions();
const TYPES = ["Topic Practice", "Weak-Topic Practice", "Revision Practice", "Exam-Style Practice"];
const DIFFICULTIES = ["Easy", "Medium", "Hard", "Mixed"];

const router = useRouter();
const onboarding = readOnboarding();
const existing = readPracticeDraft();

type PracticeForm = Required<Pick<Practice, "title" | "className" | "subject" | "qualification" | "board" | "topics" | "practiceType" | "difficulty" | "count" | "duration" | "dueDate" | "aiFeedback">> & Partial<Practice>;

function buildDefaults(): PracticeForm {
  const cls = onboarding.firstClass ?? undefined;
  return {
    title: "",
    className: cls?.name || "",
    subject: cls?.subject || onboarding.subjects?.[0] || "",
    qualification: cls?.qualification || onboarding.quals?.[0] || "GCSE",
    board: cls?.board || onboarding.boards?.[0] || "AQA",
    topics: [],
    practiceType: "Topic Practice",
    difficulty: "Medium",
    count: 10,
    duration: 20,
    dueDate: "",
    aiFeedback: true,
    ...existing,
  };
}

const f = reactive<PracticeForm>(buildDefaults());
const topicInput = ref("");
const step = ref<"config" | "review">("config");
const success = ref(false);

watch(f, () => writePracticeDraft(f), { deep: true });

function addTopic() {
  const t = topicInput.value.trim();
  if (!t) return;
  if (!f.topics?.includes(t)) f.topics = [...(f.topics || []), t];
  topicInput.value = "";
}
function removeTopic(t: string) {
  f.topics = (f.topics || []).filter((x) => x !== t);
}

function toReview() {
  if (!f.title?.trim() || !f.subject || !f.className?.trim() || !f.topics?.length) {
    toast.error("Add a title, class, subject and at least one topic.");
    return;
  }
  step.value = "review";
}

function assign() {
  const p: Practice = {
    id: uid(),
    title: f.title!,
    className: f.className!,
    subject: f.subject!,
    qualification: f.qualification!,
    board: f.board!,
    topics: f.topics!,
    practiceType: (f.practiceType as Practice["practiceType"]) || "Topic Practice",
    difficulty: (f.difficulty as Practice["difficulty"]) || "Medium",
    count: f.count || 10,
    duration: f.duration || 20,
    dueDate: f.dueDate || "",
    aiFeedback: !!f.aiFeedback,
    status: "assigned",
    createdAt: new Date().toISOString(),
  };
  savePractice(p);
  writePracticeDraft(null);
  success.value = true;
}

function resetAll() {
  success.value = false;
  step.value = "config";
  Object.assign(f, buildDefaults());
}
</script>

<template>
  <SuccessScreen
    v-if="success"
    title="Practice assigned successfully"
    :primary="{ label: 'Assign Another', onClick: resetAll }"
    :secondary="{ label: 'Return to Activities', to: '/teacher/workspace/activities' }"
    :tertiary="{ label: 'Back to Workspace', to: '/teacher/workspace' }"
  />

  <template v-else-if="step === 'review'">
    <button class="inline-flex items-center gap-1.5 text-[13px] text-ink3 hover:text-violet-600 mb-3" @click="step = 'config'">
      <ArrowLeft :size="14" /> Edit Practice Settings
    </button>
    <StepHeader eyebrow="Review Practice" :title="f.title!" subtitle="Confirm the details before assigning to students." />
    <div class="bg-bg-card border border-border rounded-[16px] p-5 md:p-6 space-y-3">
      <Row label="Class" :value="f.className || '—'" />
      <Row label="Subject" :value="`${f.subject} · ${f.qualification} ${f.board}`" />
      <Row label="Practice type" :value="f.practiceType || '—'" />
      <Row label="Topics" :value="f.topics?.join(', ') || '—'" />
      <Row label="Questions" :value="String(f.count)" />
      <Row label="Difficulty" :value="f.difficulty || '—'" />
      <Row label="Estimated duration" :value="`${f.duration} min`" />
      <Row label="Due date" :value="f.dueDate || 'Not set'" />
      <Row label="AI feedback" :value="f.aiFeedback ? 'Enabled' : 'Off'" />
    </div>
    <div class="sticky bottom-4 mt-6 flex gap-2 justify-end bg-bg-card/90 backdrop-blur border border-border rounded-full px-4 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.08)]">
      <button class="text-[13px] font-semibold text-ink2 px-4 py-2 rounded-full hover:bg-bg-soft" @click="step = 'config'">Edit Practice Settings</button>
      <button class="inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13.5px] font-semibold px-5 py-2 rounded-full hover:bg-violet-700 transition" @click="assign">
        <Target :size="14" /> Assign Practice
      </button>
    </div>
  </template>

  <template v-else>
    <button class="inline-flex items-center gap-1.5 text-[13px] text-ink3 hover:text-violet-600 mb-3" @click="router.push('/teacher/workspace')">
      <ArrowLeft :size="14" /> Back to Workspace
    </button>
    <StepHeader eyebrow="Assign Practice" title="Configure practice for your class" subtitle="Pre-filled from your teacher setup — change anything that doesn't fit." />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <FormCard title="Practice details">
        <Field label="Practice title">
          <TextInput v-model="f.title" placeholder="e.g. Algebra Weak-Topic Practice" />
        </Field>
        <Field label="Class or student group">
          <TextInput v-model="f.className" placeholder="e.g. 10A Mathematics" />
        </Field>
        <div class="grid grid-cols-2 gap-3">
          <Field label="Subject">
            <Select v-model="f.subject" :options="SUBJECTS" placeholder="Select subject" />
          </Field>
          <Field label="Practice type">
            <Select v-model="f.practiceType" :options="TYPES" />
          </Field>
          <Field label="Qualification">
            <Select v-model="f.qualification" :options="QUALS" />
          </Field>
          <Field label="Exam board">
            <Select v-model="f.board" :options="BOARDS" />
          </Field>
        </div>
      </FormCard>

      <FormCard title="Content & delivery">
        <Field label="Topics">
          <div class="flex gap-2">
            <TextInput v-model="topicInput" placeholder="Add a topic and press Enter" @keydown.enter.prevent="addTopic" />
            <button class="shrink-0 px-3 rounded-lg bg-bg-soft border border-border text-[12.5px] font-semibold text-ink2 hover:border-violet-300" @click="addTopic">
              Add
            </button>
          </div>
          <div v-if="f.topics?.length" class="flex flex-wrap gap-1.5 mt-2">
            <span v-for="t in f.topics" :key="t" class="inline-flex items-center gap-1 text-[11.5px] font-semibold px-2.5 py-1 rounded-full bg-violet-50 text-violet-700">
              {{ t }}
              <button class="text-violet-500 hover:text-violet-700" @click="removeTopic(t)">×</button>
            </span>
          </div>
        </Field>
        <div class="grid grid-cols-3 gap-3">
          <Field label="Questions">
            <NumberInput v-model="f.count" min="1" />
          </Field>
          <Field label="Difficulty">
            <Select v-model="f.difficulty" :options="DIFFICULTIES" />
          </Field>
          <Field label="Duration (min)">
            <NumberInput v-model="f.duration" min="5" />
          </Field>
        </div>
        <Field label="Due date">
          <TextInput v-model="f.dueDate" type="date" />
        </Field>
        <Toggle v-model="f.aiFeedback" label="AI feedback for students" desc="Students see explanations after each question." />
      </FormCard>
    </div>

    <div class="sticky bottom-4 mt-6 flex gap-2 justify-end bg-bg-card/90 backdrop-blur border border-border rounded-full px-4 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.08)]">
      <button class="text-[13px] font-semibold text-ink2 px-4 py-2 rounded-full hover:bg-bg-soft" @click="router.push('/teacher/workspace')">Cancel</button>
      <button class="inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13.5px] font-semibold px-5 py-2 rounded-full hover:bg-violet-700 transition" @click="toReview">
        Review Assignment
      </button>
    </div>
  </template>
</template>
