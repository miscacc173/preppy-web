<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { setPageMeta } from "@/lib/useRouteHead";
import { ArrowLeft, Wand2 } from "lucide-vue-next";
import { Field, TextInput, NumberInput, Select, Textarea, Toggle, FormCard, GenerationOverlay, StepHeader } from "@/components/teacher/creation-ui";
import { readOnboarding, readActivityDraft, writeActivityDraft, saveActivity, generateActivitySections, uid, type Activity } from "@/lib/teacherStore";
import { useCatalogOptions } from "@/data/composables/useCatalogOptions";

setPageMeta({ title: "New Learning Activity — Teacher Workspace" });

const { qualifications: QUALS, boards: BOARDS, subjects: SUBJECTS } = useCatalogOptions();
const TYPES = ["Guided Lesson", "Topic Explanation", "Revision Activity", "Worked Example", "Focused Practice", "Exam Preparation"];
const DIFFICULTIES = ["Easy", "Medium", "Hard"];

const router = useRouter();
const onboarding = readOnboarding();
const existing = readActivityDraft();

type ActivityForm = Required<Pick<Activity, "title" | "subject" | "qualification" | "board" | "className" | "topic" | "objective" | "activityType" | "difficulty" | "duration" | "components">> & Partial<Activity>;

function buildDefaults(): ActivityForm {
  const cls = onboarding.firstClass ?? undefined;
  return {
    title: "",
    subject: cls?.subject || onboarding.subjects?.[0] || "",
    qualification: cls?.qualification || onboarding.quals?.[0] || "GCSE",
    board: cls?.board || onboarding.boards?.[0] || "AQA",
    className: cls?.name || "",
    topic: "",
    objective: "",
    activityType: "Guided Lesson",
    difficulty: "Medium",
    duration: 30,
    components: { explanation: true, keyConcepts: true, workedExamples: true, practice: true, hints: true, feedback: true, summary: true },
    ...existing,
  };
}

const f = reactive<ActivityForm>(buildDefaults());
const generating = ref(false);

watch(f, () => writeActivityDraft(f), { deep: true });

function setComp(k: keyof Activity["components"], v: boolean) {
  f.components = { ...(f.components as Activity["components"]), [k]: v };
}

async function generate() {
  if (!f.title?.trim() || !f.subject || !f.topic?.trim()) {
    toast.error("Add a title, subject and topic to continue.");
    return;
  }
  generating.value = true;
  await new Promise((r) => setTimeout(r, 1600));
  const now = new Date().toISOString();
  const a: Activity = {
    id: uid(),
    title: f.title!,
    subject: f.subject!,
    qualification: f.qualification!,
    board: f.board!,
    className: f.className || "",
    topic: f.topic!,
    objective: f.objective || "",
    activityType: (f.activityType as Activity["activityType"]) || "Guided Lesson",
    difficulty: (f.difficulty as Activity["difficulty"]) || "Medium",
    duration: f.duration || 30,
    components: f.components as Activity["components"],
    sections: [],
    status: "draft",
    createdAt: now,
    updatedAt: now,
  };
  a.sections = generateActivitySections(a);
  saveActivity(a);
  writeActivityDraft(null);
  generating.value = false;
  toast.success("Learning activity generated");
  router.push(`/teacher/workspace/activities/${a.id}`);
}
</script>

<template>
  <button class="inline-flex items-center gap-1.5 text-[13px] text-ink3 hover:text-violet-600 mb-3" @click="router.push('/teacher/workspace/activities')">
    <ArrowLeft :size="14" /> Back to Learning Activities
  </button>
  <StepHeader eyebrow="Create Learning Activity" title="Configure your activity" subtitle="Choose how the activity is structured. You'll be able to edit every section after generation." />

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <FormCard title="Activity details">
      <Field label="Activity title">
        <TextInput v-model="f.title" placeholder="e.g. Introduction to Photosynthesis" />
      </Field>
      <div class="grid grid-cols-2 gap-3">
        <Field label="Subject">
          <Select v-model="f.subject" :options="SUBJECTS" placeholder="Select subject" />
        </Field>
        <Field label="Class / group">
          <TextInput v-model="f.className" placeholder="Optional" />
        </Field>
        <Field label="Qualification">
          <Select v-model="f.qualification" :options="QUALS" />
        </Field>
        <Field label="Exam board">
          <Select v-model="f.board" :options="BOARDS" />
        </Field>
      </div>
    </FormCard>

    <FormCard title="Learning intent">
      <Field label="Topic">
        <TextInput v-model="f.topic" placeholder="e.g. Quadratic equations" />
      </Field>
      <Field label="Learning objective">
        <Textarea v-model="f.objective" placeholder="What should students be able to do after this activity?" />
      </Field>
      <div class="grid grid-cols-3 gap-3">
        <Field label="Activity type">
          <Select v-model="f.activityType" :options="TYPES" />
        </Field>
        <Field label="Difficulty">
          <Select v-model="f.difficulty" :options="DIFFICULTIES" />
        </Field>
        <Field label="Duration (min)">
          <NumberInput v-model="f.duration" min="5" />
        </Field>
      </div>
    </FormCard>

    <FormCard title="Include in this activity">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <Toggle :model-value="!!f.components?.explanation" label="AI-generated explanation" @update:model-value="setComp('explanation', $event)" />
        <Toggle :model-value="!!f.components?.keyConcepts" label="Key concepts" @update:model-value="setComp('keyConcepts', $event)" />
        <Toggle :model-value="!!f.components?.workedExamples" label="Worked examples" @update:model-value="setComp('workedExamples', $event)" />
        <Toggle :model-value="!!f.components?.practice" label="Practice questions" @update:model-value="setComp('practice', $event)" />
        <Toggle :model-value="!!f.components?.hints" label="Hints" @update:model-value="setComp('hints', $event)" />
        <Toggle :model-value="!!f.components?.feedback" label="Instant feedback" @update:model-value="setComp('feedback', $event)" />
        <Toggle :model-value="!!f.components?.summary" label="End-of-activity summary" @update:model-value="setComp('summary', $event)" />
      </div>
    </FormCard>
  </div>

  <div class="sticky bottom-4 mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 justify-end bg-bg-card/90 backdrop-blur border border-border rounded-full px-4 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.08)]">
    <button class="text-[13px] font-semibold text-ink2 hover:text-ink px-4 py-2 rounded-full" @click="router.push('/teacher/workspace/activities')">Cancel</button>
    <button class="inline-flex items-center justify-center gap-2 bg-violet-600 text-white text-[13.5px] font-semibold px-5 py-2.5 rounded-full hover:bg-violet-700 transition" @click="generate">
      <Wand2 :size="15" /> Generate Learning Activity
    </button>
  </div>

  <GenerationOverlay
    :active="generating"
    :messages="[
      'Preparing your learning activity…',
      'Organising the selected topic…',
      'Creating guided explanations and practice…',
      'Preparing the activity preview…',
    ]"
  />
</template>
