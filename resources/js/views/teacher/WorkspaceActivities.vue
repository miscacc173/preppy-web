<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import { Wand2, Target, Trash2 } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { PageHeader, EmptyState, Tabs, Section } from "@/components/teacher/workspace-ui";
import { listActivities, listPractice, deletePractice, type Activity, type Practice } from "@/lib/teacherStore";

setPageMeta({ title: "Learning Activities — Teacher Workspace" });

const TABS = ["All", "Draft", "Assigned"];

const tab = ref("All");
const items = ref<Activity[]>([]);
const practice = ref<Practice[]>([]);
const router = useRouter();

function refresh() {
  items.value = listActivities();
  practice.value = listPractice();
}
onMounted(refresh);

const filtered = computed(() => (tab.value === "All" ? items.value : items.value.filter((a) => a.status === tab.value.toLowerCase())));

function createActivity() {
  router.push("/teacher/workspace/activities/new");
}
function assignPractice() {
  router.push("/teacher/workspace/practice/new");
}
function removePractice(id: string) {
  deletePractice(id);
  toast.success("Practice removed");
  refresh();
}
</script>

<template>
  <PageHeader
    title="Learning Activities"
    subtitle="Create guided lessons, revision activities and focused practice for your students."
    :primary-action="{ label: 'Create Learning Activity', onClick: createActivity }"
  />
  <Tabs :tabs="TABS" v-model:active="tab" />

  <EmptyState
    v-if="filtered.length === 0"
    :icon="Wand2"
    :title="`No ${tab === 'All' ? '' : tab.toLowerCase() + ' '}learning activities yet`"
    desc="Prepare a lesson, revision task or focused practice activity and assign it to a class or student group."
    cta="Create Learning Activity"
    @cta="createActivity"
  />
  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <RouterLink
      v-for="a in filtered"
      :key="a.id"
      :to="`/teacher/workspace/activities/${a.id}`"
      class="bg-bg-card border border-border rounded-[14px] p-4 hover:border-violet-300 hover:-translate-y-[1px] transition-all block"
    >
      <div class="flex items-center gap-2 mb-1">
        <Wand2 :size="13" class="text-violet-600" />
        <span :class="`text-[10.5px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded ${a.status === 'draft' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'}`">
          {{ a.status }}
        </span>
        <span class="text-[10.5px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-bg-soft text-ink3">{{ a.activityType }}</span>
      </div>
      <div class="text-[15px] font-bold text-ink tracking-[-0.01em] truncate">{{ a.title }}</div>
      <div class="text-[12.5px] text-ink3 font-light mt-0.5 truncate">{{ [a.subject, a.topic, a.qualification, a.board].filter(Boolean).join(" · ") }}</div>
      <div class="flex flex-wrap gap-x-4 gap-y-1 text-[12px] text-ink3 mt-2">
        <span>{{ a.sections.length }} sections</span>
        <span>{{ a.duration }} min</span>
        <span v-if="a.assignedTo">{{ a.assignedTo }}</span>
      </div>
    </RouterLink>
  </div>

  <div class="mt-8">
    <Section title="Assigned Practice" subtitle="Focused practice sessions you've assigned to classes or groups.">
      <template #action>
        <button class="text-[13px] font-semibold text-violet-600 hover:text-violet-700 inline-flex items-center gap-1" @click="assignPractice">
          Assign Practice
        </button>
      </template>

      <EmptyState
        v-if="practice.length === 0"
        :icon="Target"
        title="No practice assigned yet"
        desc="Send a focused, topic-specific practice session to a class or group of students."
        cta="Assign Practice"
        @cta="assignPractice"
      />
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div v-for="p in practice" :key="p.id" class="bg-bg-card border border-border rounded-[14px] p-4 hover:border-violet-300 transition">
          <div class="flex items-center gap-2 mb-1">
            <Target :size="13" class="text-blue-600" />
            <span class="text-[10.5px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">assigned</span>
            <span class="text-[10.5px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-bg-soft text-ink3">{{ p.practiceType }}</span>
          </div>
          <div class="text-[15px] font-bold text-ink tracking-[-0.01em] truncate">{{ p.title }}</div>
          <div class="text-[12.5px] text-ink3 font-light mt-0.5 truncate">{{ [p.className, p.subject, p.qualification, p.board].filter(Boolean).join(" · ") }}</div>
          <div class="flex flex-wrap gap-x-4 gap-y-1 text-[12px] text-ink3 mt-2">
            <span>{{ p.count }} questions</span>
            <span>{{ p.duration }} min</span>
            <span>{{ p.difficulty }}</span>
            <span v-if="p.dueDate">Due {{ p.dueDate }}</span>
          </div>
          <div class="mt-3 flex justify-end">
            <button
              class="inline-flex items-center gap-1 text-[11.5px] font-semibold text-ink3 hover:text-red-600 px-2 py-1 rounded-md border border-transparent hover:border-red-200"
              @click="removePractice(p.id)"
            >
              <Trash2 :size="12" /> Remove
            </button>
          </div>
        </div>
      </div>
    </Section>
  </div>
</template>
