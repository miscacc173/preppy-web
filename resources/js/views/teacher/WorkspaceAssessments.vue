<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { setPageMeta } from "@/lib/useRouteHead";
import { ClipboardList, Wand2 } from "lucide-vue-next";
import { PageHeader, EmptyState, Tabs } from "@/components/teacher/workspace-ui";
import { listAssessments, type Assessment } from "@/lib/teacherStore";

setPageMeta({ title: "Assessments — Teacher Workspace" });

const TABS = ["All", "Draft", "Assigned"];

const tab = ref("All");
const items = ref<Assessment[]>([]);
const router = useRouter();

onMounted(() => {
  items.value = listAssessments();
});

const filtered = computed(() => (tab.value === "All" ? items.value : items.value.filter((a) => a.status === tab.value.toLowerCase())));

function createNew() {
  router.push("/teacher/workspace/assessments/new");
}
</script>

<template>
  <PageHeader
    title="Assessments"
    subtitle="Create, assign and review exam-board-aligned assessments."
    :primary-action="{ label: 'Create Assessment', onClick: createNew }"
  />
  <Tabs :tabs="TABS" v-model:active="tab" />

  <EmptyState
    v-if="filtered.length === 0"
    :icon="ClipboardList"
    :title="`No ${tab === 'All' ? '' : tab.toLowerCase() + ' '}assessments yet`"
    desc="Build an exam-board-aligned assessment, schedule it to a class and review submissions once students respond."
    cta="Create Assessment"
    @cta="createNew"
  />
  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <RouterLink
      v-for="a in filtered"
      :key="a.id"
      :to="`/teacher/workspace/assessments/${a.id}`"
      class="bg-bg-card border border-border rounded-[14px] p-4 hover:border-violet-300 hover:-translate-y-[1px] transition-all block"
    >
      <div class="flex items-start justify-between gap-3 mb-2">
        <div class="min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <Wand2 :size="13" class="text-violet-600" />
            <span :class="`text-[10.5px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded ${a.status === 'draft' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'}`">
              {{ a.status }}
            </span>
          </div>
          <div class="text-[15px] font-bold text-ink tracking-[-0.01em] truncate">{{ a.title }}</div>
          <div class="text-[12.5px] text-ink3 font-light mt-0.5 truncate">{{ [a.subject, a.qualification, a.board].filter(Boolean).join(" · ") }}</div>
        </div>
      </div>
      <div class="flex flex-wrap gap-x-4 gap-y-1 text-[12px] text-ink3">
        <span>{{ a.questions.length }} questions</span>
        <span>{{ a.totalMarks }} marks</span>
        <span>{{ a.duration }} min</span>
        <span v-if="a.assignedTo">{{ a.assignedTo }}</span>
      </div>
    </RouterLink>
  </div>
</template>
