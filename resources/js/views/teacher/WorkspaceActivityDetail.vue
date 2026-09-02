<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { setPageMeta } from "@/lib/useRouteHead";
import { ArrowLeft, Pencil, Eye, Settings2 } from "lucide-vue-next";
import { getActivity, saveActivity, generateActivitySections, uid, type Activity, type ActivitySection } from "@/lib/teacherStore";
import SuccessScreen from "./SuccessScreen.vue";
import AssignModal from "./AssignModal.vue";
import type { AssignData } from "./assign-types";
import SectionCard from "./activity-detail/SectionCard.vue";
import AddSectionRow from "./activity-detail/AddSectionRow.vue";

setPageMeta({ title: "Learning Activity Review — Teacher Workspace" });

const KINDS = ["objective", "introduction", "keyConcepts", "explanation", "workedExample", "practice", "hints", "check", "summary"] as const;

const route = useRoute();
const router = useRouter();
const a = ref<Activity | null>(null);
const mode = ref<"teacher" | "student">("teacher");
const assignOpen = ref(false);
const success = ref(false);

onMounted(() => {
  const loaded = getActivity(route.params.id as string);
  if (!loaded) {
    toast.error("Activity not found");
    router.push("/teacher/workspace/activities");
    return;
  }
  a.value = loaded;
});

function persist(next: Activity) {
  a.value = next;
  saveActivity(next);
}

function updateTitle(title: string) {
  if (a.value) persist({ ...a.value, title });
}

function updateSection(s: ActivitySection) {
  if (!a.value) return;
  persist({ ...a.value, sections: a.value.sections.map((x) => (x.id === s.id ? s : x)) });
}

function moveSection(i: number, dir: -1 | 1) {
  if (!a.value) return;
  const arr = [...a.value.sections];
  const j = i + dir;
  if (j < 0 || j >= arr.length) return;
  [arr[i], arr[j]] = [arr[j], arr[i]];
  persist({ ...a.value, sections: arr });
}

function deleteSection(id: string) {
  if (!a.value) return;
  persist({ ...a.value, sections: a.value.sections.filter((x) => x.id !== id) });
}

function regenerateSection(s: ActivitySection) {
  if (!a.value) return;
  const fresh = generateActivitySections(a.value).find((x) => x.kind === s.kind);
  if (fresh) persist({ ...a.value, sections: a.value.sections.map((x) => (x.id === s.id ? { ...fresh, id: s.id, title: s.title } : x)) });
}

function addSection(kind: ActivitySection["kind"]) {
  if (!a.value) return;
  persist({
    ...a.value,
    sections: [...a.value.sections, { id: uid(), kind, title: kind.charAt(0).toUpperCase() + kind.slice(1), body: "New section — click to edit." }],
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
    assignedTo: d.className,
    startDate: d.startDate,
    dueDate: d.dueDate,
    instructions: d.instructions,
  });
  assignOpen.value = false;
  success.value = true;
}
</script>

<template>
  <template v-if="a">
    <SuccessScreen
      v-if="success"
      title="Learning activity assigned successfully"
      :primary="{ label: 'View Activity', onClick: () => (success = false) }"
      :secondary="{ label: 'Return to Activities', to: '/teacher/workspace/activities' }"
      :tertiary="{ label: 'Create Another Activity', to: '/teacher/workspace/activities/new' }"
    />

    <template v-else>
      <button class="inline-flex items-center gap-1.5 text-[13px] text-ink3 hover:text-violet-600 mb-3" @click="router.push('/teacher/workspace/activities')">
        <ArrowLeft :size="14" /> Back to Activities
      </button>

      <div class="bg-bg-card border border-border rounded-[16px] p-5 md:p-6 mb-4">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span :class="`text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full ${a.status === 'draft' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'}`">{{ a.status }}</span>
              <span class="text-[11.5px] text-ink3 font-medium">{{ a.activityType }} · ~{{ a.duration }} min</span>
            </div>
            <input
              :value="a.title"
              class="w-full bg-transparent text-[22px] md:text-[24px] font-bold text-ink tracking-[-0.02em] outline-none focus:bg-bg-soft rounded px-1 -mx-1"
              @input="updateTitle(($event.target as HTMLInputElement).value)"
            />
            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-[12.5px] text-ink2">
              <span><span class="text-ink3">Subject:</span> <b class="text-ink">{{ a.subject }}</b></span>
              <span><span class="text-ink3">Topic:</span> <b class="text-ink">{{ a.topic }}</b></span>
              <span><span class="text-ink3">Qual:</span> <b class="text-ink">{{ a.qualification }}</b></span>
              <span><span class="text-ink3">Board:</span> <b class="text-ink">{{ a.board }}</b></span>
            </div>
            <p v-if="a.objective" class="text-[13px] text-ink2 font-light mt-2 leading-[1.6]">
              <span class="font-semibold text-ink">Objective:</span> {{ a.objective }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <RouterLink to="/teacher/workspace/activities/new" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-ink2 border border-border bg-bg-card px-3 py-2 rounded-full hover:border-violet-300">
              <Settings2 :size="13" /> Edit Activity Settings
            </RouterLink>
            <div class="inline-flex bg-bg-soft border border-border rounded-full p-0.5">
              <button :class="`inline-flex items-center gap-1 text-[12px] font-semibold px-3 py-1.5 rounded-full ${mode === 'teacher' ? 'bg-bg-card text-violet-700 shadow-sm' : 'text-ink3'}`" @click="mode = 'teacher'">
                <Pencil :size="12" /> Teacher Review
              </button>
              <button :class="`inline-flex items-center gap-1 text-[12px] font-semibold px-3 py-1.5 rounded-full ${mode === 'student' ? 'bg-bg-card text-violet-700 shadow-sm' : 'text-ink3'}`" @click="mode = 'student'">
                <Eye :size="12" /> Preview as Student
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <SectionCard
          v-for="(s, i) in a.sections"
          :key="s.id"
          :index="i"
          :total="a.sections.length"
          :s="s"
          :mode="mode"
          @update:s="updateSection"
          @move="(dir) => moveSection(i, dir)"
          @delete="deleteSection(s.id)"
          @regenerate="regenerateSection(s)"
        />
        <AddSectionRow v-if="mode === 'teacher'" :existing="a.sections.map((s) => s.kind)" :kinds="KINDS" @add="addSection" />
      </div>

      <div class="sticky bottom-4 mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 justify-end bg-bg-card/90 backdrop-blur border border-border rounded-full px-4 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.08)]">
        <button class="text-[13px] font-semibold text-ink2 border border-border bg-bg-card px-4 py-2 rounded-full hover:border-violet-300" @click="saveDraft">Save as Draft</button>
        <button class="inline-flex items-center gap-1.5 bg-violet-600 text-white text-[13.5px] font-semibold px-5 py-2 rounded-full hover:bg-violet-700 transition" @click="assignOpen = true">
          Assign Activity
        </button>
      </div>

      <AssignModal
        v-if="assignOpen"
        title="Assign learning activity"
        :defaults="{ className: a.className, startDate: '', dueDate: a.dueDate || '', timed: false, instructions: '', aiFeedback: true }"
        @close="assignOpen = false"
        @assign="onAssign"
      />
    </template>
  </template>
</template>
