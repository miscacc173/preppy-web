<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { toast } from "vue-sonner";
import { setPageMeta } from "@/lib/useRouteHead";
import { School } from "lucide-vue-next";
import { PageHeader, EmptyState, FilterBar, FilterSelect, SearchInput } from "@/components/teacher/workspace-ui";
import { listClasses, saveClass, deleteClass, type TeacherClass } from "@/lib/teacherStore";
import ClassRow from "./workspace-classes/ClassRow.vue";
import CreateClassModal from "./workspace-classes/CreateClassModal.vue";
import { useCatalogOptions } from "@/data/composables/useCatalogOptions";

setPageMeta({ title: "My Classes — Teacher Workspace" });

const { qualifications: QUALS, boards: BOARDS, subjects: SUBJECTS } = useCatalogOptions();

const q = ref("");
const subject = ref("All subjects");
const qual = ref("All qualifications");
const board = ref("All boards");
const items = ref<TeacherClass[]>([]);
const open = ref(false);

onMounted(() => {
  items.value = listClasses();
});

const filtered = computed(() =>
  items.value.filter((c) => {
    if (q.value && !c.name.toLowerCase().includes(q.value.toLowerCase())) return false;
    if (subject.value !== "All subjects" && c.subject !== subject.value) return false;
    if (qual.value !== "All qualifications" && c.qualification !== qual.value) return false;
    if (board.value !== "All boards" && c.board !== board.value) return false;
    return true;
  }),
);

function refresh() {
  items.value = listClasses();
}

function onDelete(id: string) {
  deleteClass(id);
  toast.success("Class removed");
  refresh();
}

function onSave(c: TeacherClass) {
  saveClass(c);
  toast.success("Class created");
  open.value = false;
  refresh();
}
</script>

<template>
  <PageHeader
    title="My Classes"
    subtitle="Manage your classes, students, subjects and learning activity."
    :primary-action="{ label: 'Create New Class', onClick: () => (open = true) }"
  />
  <FilterBar>
    <SearchInput v-model="q" placeholder="Search classes…" />
    <FilterSelect v-model="subject" label="Subject" :options="['All subjects', ...SUBJECTS]" />
    <FilterSelect v-model="qual" label="Qualification" :options="['All qualifications', ...QUALS]" />
    <FilterSelect v-model="board" label="Board" :options="['All boards', ...BOARDS]" />
  </FilterBar>

  <EmptyState
    v-if="filtered.length === 0"
    :icon="School"
    :title="items.length === 0 ? 'No classes yet' : 'No classes match your filters'"
    :desc="items.length === 0 ? 'Create your first class to organise students, assign learning activities and track progress.' : 'Adjust filters or clear the search to see more.'"
    :cta="items.length === 0 ? 'Create First Class' : undefined"
    @cta="open = true"
  />
  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <ClassRow v-for="c in filtered" :key="c.id" :c="c" @delete="onDelete(c.id)" />
  </div>

  <CreateClassModal v-if="open" @close="open = false" @save="onSave" />
</template>
