<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Search, UsersRound } from "lucide-vue-next";
import { useRoute } from "vue-router";
import DataPanel from "@/components/operations/DataPanel.vue";
import ParentAccessModal from "@/components/operations/ParentAccessModal.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import type { UiStudentRecord } from "@/data/models/operations";

const route = useRoute();
const students = ref<UiStudentRecord[]>([]);
const selected = ref<UiStudentRecord | null>(null);
const loading = ref(true);
const error = ref("");
const search = ref("");
const instituteId = () => Number(route.params.instituteId);
const filtered = computed(() => students.value.filter((student) => `${student.name} ${student.email} ${student.courseName}`.toLowerCase().includes(search.value.toLowerCase())));

onMounted(async () => {
  try {
    students.value = (await operationsRepository.students({ institute_id: instituteId(), per_page: 100 })).items;
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Unable to load students.";
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="space-y-5">
    <div>
      <p class="text-[12px] font-semibold uppercase tracking-[0.12em] text-blue">Family visibility</p>
      <h2 class="mt-1 text-[26px] font-bold">Parent access</h2>
      <p class="mt-1 text-[13px] text-ink3">Link parents to institute students and revoke access when required.</p>
    </div>
    <p v-if="error" class="rounded-xl bg-pink-light p-3 text-[12px] text-pink">{{ error }}</p>
    <label class="flex max-w-sm items-center gap-2 rounded-xl border border-border bg-bg-card px-3 py-2.5">
      <Search :size="15" class="text-ink4" /><input v-model="search" class="w-full bg-transparent text-[12px] outline-none" placeholder="Search students" />
    </label>
    <DataPanel title="Students" :description="`${filtered.length} students`" :loading="loading" :empty="!filtered.length">
      <div class="grid gap-3 p-4 md:grid-cols-2">
        <button v-for="student in filtered" :key="student.userId" class="flex items-center justify-between rounded-xl border border-border p-4 text-left transition hover:bg-bg-soft" @click="selected = student">
          <span><strong class="block text-[13px]">{{ student.name }}</strong><span class="text-[11px] text-ink3">{{ student.email }} · {{ student.courseName || 'Course not set' }}</span></span>
          <UsersRound :size="17" class="shrink-0 text-blue" />
        </button>
      </div>
    </DataPanel>
    <ParentAccessModal :open="Boolean(selected)" :student="selected ? { userId: selected.userId, name: selected.name } : null" @close="selected = null" />
  </div>
</template>
