<script setup lang="ts">
import { computed, watch } from "vue";
import type { UiCatalog } from "@/data/models/platform";
import { activeCourses, boardsForCourse, subjectsForCourse } from "@/data/academicSelection";

const props = withDefaults(defineProps<{
  catalog: UiCatalog | null;
  courseId: number | null;
  boardId: number | null;
  subjectIds: number[];
  singleSubject?: boolean;
}>(), { singleSubject: false });
const emit = defineEmits<{
  "update:courseId": [value: number | null];
  "update:boardId": [value: number | null];
  "update:subjectIds": [value: number[]];
}>();

const courses = computed(() => activeCourses(props.catalog));
const boards = computed(() => boardsForCourse(props.catalog, props.courseId));
const subjects = computed(() => subjectsForCourse(props.catalog, props.courseId));

watch(() => props.courseId, () => {
  emit("update:boardId", null);
  emit("update:subjectIds", []);
});

function toggleSubject(id: number) {
  if (props.singleSubject) return emit("update:subjectIds", [id]);
  emit("update:subjectIds", props.subjectIds.includes(id) ? props.subjectIds.filter((value) => value !== id) : [...props.subjectIds, id]);
}
</script>

<template>
  <div class="space-y-3.5">
    <label class="block text-[12px] font-medium text-ink2">Course
      <select :value="courseId ?? ''" required class="mt-1.5 w-full rounded-md border border-border bg-bg-card px-3.5 py-2.5 text-[14px] outline-none focus:border-blue" @change="emit('update:courseId', Number(($event.target as HTMLSelectElement).value) || null)">
        <option value="" disabled>Select course</option>
        <option v-for="course in courses" :key="course.id" :value="course.id" :disabled="course.status !== 'active'">{{ course.name }}{{ course.status === 'coming_soon' ? ' — Coming soon' : '' }}</option>
      </select>
    </label>
    <label class="block text-[12px] font-medium text-ink2">Exam board
      <select :value="boardId ?? ''" required :disabled="!courseId" class="mt-1.5 w-full rounded-md border border-border bg-bg-card px-3.5 py-2.5 text-[14px] outline-none focus:border-blue disabled:opacity-50" @change="emit('update:boardId', Number(($event.target as HTMLSelectElement).value) || null)">
        <option value="" disabled>{{ courseId ? 'Select an available board' : 'Select a course first' }}</option>
        <option v-for="board in boards" :key="board.id" :value="board.id" :disabled="board.status !== 'active'">{{ board.name }}{{ board.status === 'coming_soon' ? ' — Coming soon' : '' }}</option>
      </select>
    </label>
    <fieldset>
      <legend class="mb-2 text-[12px] font-medium text-ink2">{{ singleSubject ? 'Subject' : 'Subjects' }}</legend>
      <p v-if="!courseId" class="rounded-md border border-border bg-bg-soft px-3 py-2 text-[12px] text-ink3">Select a course to see its subjects.</p>
      <div v-else class="grid max-h-40 gap-2 overflow-y-auto rounded-md border border-border p-3 sm:grid-cols-2">
        <label v-for="subject in subjects" :key="subject.id" class="flex items-center gap-2 text-[12px]" :class="subject.status !== 'active' ? 'text-ink4' : 'text-ink2'">
          <input :type="singleSubject ? 'radio' : 'checkbox'" :checked="subjectIds.includes(subject.id)" :disabled="subject.status !== 'active'" @change="toggleSubject(subject.id)" />
          <span>{{ subject.name }}</span><span v-if="subject.status === 'coming_soon'" class="rounded-full bg-gold-light px-1.5 py-0.5 text-[8px] font-bold uppercase text-gold">Coming soon</span>
        </label>
      </div>
    </fieldset>
  </div>
</template>
