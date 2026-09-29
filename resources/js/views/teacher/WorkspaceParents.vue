<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { LoaderCircle, Search, UsersRound } from "lucide-vue-next";
import ParentAccessModal from "@/components/operations/ParentAccessModal.vue";
import { learningSessionRepository } from "@/data/repositories/learningSessionRepository";
import type { UiTutorStudent } from "@/data/models/learningSession";

const students = ref<UiTutorStudent[]>([]);
const selected = ref<UiTutorStudent | null>(null);
const loading = ref(true);
const error = ref("");
const search = ref("");
const filtered = computed(() => students.value.filter((student) => student.canManageParents && `${student.name} ${student.email} ${student.subjects.join(" ")}`.toLowerCase().includes(search.value.toLowerCase())));

onMounted(async () => {
  try { students.value = await learningSessionRepository.teacherStudents(); }
  catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load granted students."; }
  finally { loading.value = false; }
});
</script>

<template>
  <div class="space-y-5">
    <div><p class="label-mono text-violet-600">Delegated access</p><h1 class="mt-1 text-[25px] font-bold">Parent access</h1><p class="mt-1 text-[13px] text-ink3">Parent links can be changed only where an administrator enabled parent management on your student scope.</p></div>
    <label class="flex max-w-sm items-center gap-2 rounded-xl border border-border bg-white px-3 py-2"><Search :size="15" class="text-ink4" /><input v-model="search" class="w-full bg-transparent text-[12px] outline-none" placeholder="Search granted students" /></label>
    <div v-if="loading" class="grid place-items-center rounded-2xl border border-border bg-white py-20"><LoaderCircle class="animate-spin text-violet-600" /></div>
    <p v-else-if="error" class="rounded-xl bg-red-50 p-4 text-[12px] text-red-700">{{ error }}</p>
    <p v-else-if="!filtered.length" class="rounded-2xl border border-dashed border-border bg-white p-10 text-center text-[13px] text-ink3">No parent-management grant is active. Ask a platform or institute administrator to enable it on a student scope.</p>
    <div v-else class="grid gap-3 md:grid-cols-2">
      <button v-for="student in filtered" :key="student.userId" class="flex items-center justify-between rounded-2xl border border-border bg-white p-4 text-left transition hover:-translate-y-0.5 hover:shadow-md" @click="selected = student"><span><strong class="block text-[13px]">{{ student.name }}</strong><span class="text-[11px] text-ink3">{{ student.email }} · {{ student.course || 'Course not set' }}</span></span><UsersRound :size="17" class="text-violet-600" /></button>
    </div>
    <ParentAccessModal :open="Boolean(selected)" :student="selected ? { userId: selected.userId, name: selected.name } : null" @close="selected = null" />
  </div>
</template>
