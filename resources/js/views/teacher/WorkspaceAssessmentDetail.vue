<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { setPageMeta } from "@/lib/useRouteHead";
import { ArrowLeft, Plus, Pencil, Eye, Settings2 } from "lucide-vue-next";
import { getAssessment, saveAssessment, regenerateQuestion, uid, type Assessment, type AssessmentQuestion } from "@/lib/teacherStore";
import SuccessScreen from "./SuccessScreen.vue";
import AssignModal from "./AssignModal.vue";
import type { AssignData } from "./assign-types";
import QuestionCard from "./assessment-detail/QuestionCard.vue";
import StatusPill from "./assessment-detail/StatusPill.vue";
import Meta from "./assessment-detail/Meta.vue";
import ModeBtn from "./assessment-detail/ModeBtn.vue";

setPageMeta({ title: "Assessment Review — Teacher Workspace" });

const route = useRoute();
const router = useRouter();
const a = ref<Assessment | null>(null);
const mode = ref<"teacher" | "student">("teacher");
const assignOpen = ref(false);
const success = ref(false);

onMounted(() => {
  const loaded = getAssessment(route.params.id as string);
  if (!loaded) {
    toast.error("Assessment not found");
    router.push("/teacher/workspace/assessments");
    return;
  }
  a.value = loaded;
});

const totalMarks = computed(() => a.value?.questions.reduce((n, q) => n + (q.marks || 0), 0) ?? 0);

function persist(next: Assessment) {
  a.value = next;
  saveAssessment(next);
}

function updateTitle(title: string) {
  if (a.value) persist({ ...a.value, title });
}

function updateQuestion(q: AssessmentQuestion) {
  if (!a.value) return;
  persist({ ...a.value, questions: a.value.questions.map((x) => (x.id === q.id ? q : x)) });
}

function moveQuestion(i: number, dir: -1 | 1) {
  if (!a.value) return;
  const arr = [...a.value.questions];
  const j = i + dir;
  if (j < 0 || j >= arr.length) return;
  [arr[i], arr[j]] = [arr[j], arr[i]];
  persist({ ...a.value, questions: arr });
}

function duplicateQuestion(i: number, q: AssessmentQuestion) {
  if (!a.value) return;
  const arr = [...a.value.questions];
  arr.splice(i + 1, 0, { ...q, id: uid() });
  persist({ ...a.value, questions: arr });
}

function deleteQuestion(id: string) {
  if (!a.value) return;
  persist({ ...a.value, questions: a.value.questions.filter((x) => x.id !== id) });
}

function regenerateQuestionAt(q: AssessmentQuestion) {
  if (!a.value) return;
  persist({ ...a.value, questions: a.value.questions.map((x) => (x.id === q.id ? regenerateQuestion(x, a.value!.subject) : x)) });
}

function addQuestion() {
  if (!a.value) return;
  persist({
    ...a.value,
    questions: [
      ...a.value.questions,
      {
        id: uid(),
        text: "New question — click to edit.",
        type: (a.value.qtypes[0] as AssessmentQuestion["type"]) || "Short Answer",
        topic: a.value.topics[0] || "General",
        difficulty: "Medium",
        marks: Math.max(1, Math.round(a.value.totalMarks / Math.max(1, a.value.count))),
        expectedAnswer: "",
        markPoints: [],
        guidance: "",
      },
    ],
  });
}

function saveDraft() {
  if (!a.value) return;
  persist({ ...a.value, status: "draft" });
  toast.success("Saved as draft");
}

function onAssign(d: AssignData) {
  if (!a.value) return;
  persist({
    ...a.value,
    status: "assigned",
    className: d.className,
    startDate: d.startDate,
    dueDate: d.dueDate,
    timed: d.timed,
    instructions: d.instructions,
    aiFeedback: d.aiFeedback,
    assignedTo: d.className,
  });
  assignOpen.value = false;
  success.value = true;
}
</script>

<template>
  <template v-if="a">
    <SuccessScreen
      v-if="success"
      title="Assessment assigned successfully"
      :primary="{ label: 'View Assessment', onClick: () => (success = false) }"
      :secondary="{ label: 'Return to Assessments', to: '/teacher/workspace/assessments' }"
      :tertiary="{ label: 'Create Another Assessment', to: '/teacher/workspace/assessments/new' }"
    />

    <template v-else>
      <button class="inline-flex items-center gap-1.5 text-[13px] text-ink3 hover:text-violet-600 mb-3" @click="router.push('/teacher/workspace/assessments')">
        <ArrowLeft :size="14" /> Back to Assessments
      </button>

      <!-- Header -->
      <div class="bg-bg-card border border-border rounded-[16px] p-5 md:p-6 mb-4">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-2">
              <StatusPill :status="a.status" />
              <span class="text-[11.5px] text-ink3 font-medium">Updated {{ new Date(a.updatedAt).toLocaleString() }}</span>
            </div>
            <input
              :value="a.title"
              class="w-full bg-transparent text-[22px] md:text-[24px] font-bold text-ink tracking-[-0.02em] outline-none focus:bg-bg-soft rounded px-1 -mx-1"
              @input="updateTitle(($event.target as HTMLInputElement).value)"
            />
            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[12.5px] text-ink2">
              <Meta label="Subject" :value="a.subject" />
              <Meta label="Qualification" :value="a.qualification" />
              <Meta label="Board" :value="a.board" />
              <Meta label="Questions" :value="String(a.questions.length)" />
              <Meta label="Marks" :value="String(totalMarks)" />
              <Meta label="Duration" :value="`${a.duration} min`" />
            </div>
            <div v-if="a.topics.length" class="flex flex-wrap gap-1.5 mt-3">
              <span v-for="t in a.topics" :key="t" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-violet-50 text-violet-700">{{ t }}</span>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <RouterLink to="/teacher/workspace/assessments/new" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-ink2 border border-border bg-bg-card px-3 py-2 rounded-full hover:border-violet-300">
              <Settings2 :size="13" /> Edit Assessment Settings
            </RouterLink>
            <div class="inline-flex bg-bg-soft border border-border rounded-full p-0.5">
              <ModeBtn :on="mode === 'teacher'" @click="mode = 'teacher'"><Pencil :size="12" /> Teacher Review</ModeBtn>
              <ModeBtn :on="mode === 'student'" @click="mode = 'student'"><Eye :size="12" /> Preview as Student</ModeBtn>
            </div>
          </div>
        </div>
      </div>

      <!-- Questions -->
      <div class="space-y-3">
        <QuestionCard
          v-for="(q, i) in a.questions"
          :key="q.id"
          :index="i"
          :total="a.questions.length"
          :q="q"
          :mode="mode"
          @update:q="updateQuestion"
          @move="(dir) => moveQuestion(i, dir)"
          @duplicate="duplicateQuestion(i, q)"
          @delete="deleteQuestion(q.id)"
          @regenerate="regenerateQuestionAt(q)"
        />

        <button
          v-if="mode === 'teacher'"
          class="w-full inline-flex items-center justify-center gap-1.5 text-[13px] font-semibold text-violet-700 bg-violet-50 hover:bg-violet-100 border border-dashed border-violet-200 rounded-[14px] py-3 transition"
          @click="addQuestion"
        >
          <Plus :size="14" /> Add a question
        </button>
      </div>

      <!-- Sticky action bar -->
      <div class="sticky bottom-4 mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 justify-between bg-bg-card/90 backdrop-blur border border-border rounded-full px-4 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.08)]">
        <div class="text-[12.5px] text-ink3 font-medium px-2">{{ a.questions.length }} questions · {{ totalMarks }} marks</div>
        <div class="flex flex-wrap gap-2 justify-end">
          <button class="text-[13px] font-semibold text-ink2 border border-border bg-bg-card px-4 py-2 rounded-full hover:border-violet-300" @click="saveDraft">Save as Draft</button>
          <button class="inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13.5px] font-semibold px-5 py-2 rounded-full hover:bg-violet-700 transition" @click="assignOpen = true">
            Assign Assessment
          </button>
        </div>
      </div>

      <AssignModal
        v-if="assignOpen"
        :defaults="{ className: a.className, startDate: '', dueDate: a.dueDate, timed: a.timed, instructions: '', aiFeedback: a.aiFeedback }"
        @close="assignOpen = false"
        @assign="onAssign"
      />
    </template>
  </template>
</template>
