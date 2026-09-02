<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { CalendarDays, LoaderCircle, Save, Target } from "lucide-vue-next";
import AppShell from "@/components/app/AppShell.vue";
import { platformRepository } from "@/data/repositories/platformRepository";
import { studentWorkspaceRepository } from "@/data/repositories/studentWorkspaceRepository";
import type { UiStudentGoals } from "@/data/models/studentWorkspace";
import { setPageMeta } from "@/lib/useRouteHead";

setPageMeta({ title: "Goals & Targets — AiStar Prep" });
const loading = ref(true); const saving = ref(false); const saved = ref(false); const error = ref("");
const form = reactive<UiStudentGoals>({ weeklyHours: 5, examDate: "", subjectGoals: [] });
const grades = ["A*", "A", "B", "C", "D", "E", "9", "8", "7", "6", "5", "4"];
async function load() { loading.value = true; try { const [bundle, goals] = await Promise.all([platformRepository.account(), studentWorkspaceRepository.goals()]); const existing = new Map(goals.subjectGoals.map((goal) => [goal.subjectId, goal.targetGrade])); Object.assign(form, { weeklyHours: goals.weeklyHours || 5, examDate: goals.examDate, subjectGoals: bundle.account.subjects.map((subject) => ({ subjectId: subject.id, subject: subject.name, targetGrade: existing.get(subject.id) || bundle.account.preferences.targetGrades[subject.name] || "" })) }); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load goals."; } finally { loading.value = false; } }
async function save() { saving.value = true; saved.value = false; error.value = ""; try { await studentWorkspaceRepository.saveGoals({ weeklyHours: form.weeklyHours, examDate: form.examDate, subjectGoals: form.subjectGoals.map((goal) => ({ ...goal })) }); await studentWorkspaceRepository.notify({ title: "Goals updated", body: "Your study targets and exam date were updated.", category: "study", to: "/goals" }); saved.value = true; } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to save goals."; } finally { saving.value = false; } }
onMounted(load);
</script>

<template>
  <AppShell title="Goals & Targets" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="mx-auto max-w-3xl space-y-5"><div><p class="label-mono">Personal targets</p><h2 class="mt-1 text-[26px] font-bold tracking-[-0.03em]">Study goals</h2><p class="mt-1 text-[13px] text-ink3">Set targets for the subjects selected in Student Setup.</p></div><p v-if="error" class="rounded-xl bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>
      <div v-if="loading" class="space-y-3"><div v-for="item in 4" :key="item" class="h-20 animate-pulse rounded-2xl bg-bg-soft" /></div>
      <form v-else class="space-y-5" @submit.prevent="save"><section class="rounded-2xl border border-border bg-bg-card p-5"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-light text-blue"><Target :size="18" /></span><div><h3 class="text-[15px] font-semibold">Subject targets</h3><p class="text-[12px] text-ink3">Only Admin-managed subjects assigned to your profile appear here.</p></div></div><div v-if="form.subjectGoals.length" class="mt-5 divide-y divide-border"><label v-for="goal in form.subjectGoals" :key="goal.subjectId" class="flex items-center justify-between gap-4 py-3"><span class="text-[13px] font-semibold">{{ goal.subject }}</span><select v-model="goal.targetGrade" class="ops-input max-w-32"><option value="">No target</option><option v-for="grade in grades" :key="grade" :value="grade">{{ grade }}</option></select></label></div><p v-else class="mt-5 text-[13px] text-ink3">Choose subjects in Student Setup before setting subject goals.</p></section>
        <section class="grid gap-4 rounded-2xl border border-border bg-bg-card p-5 sm:grid-cols-2"><label class="text-[11px] font-semibold">Weekly study hours<input v-model.number="form.weeklyHours" type="number" min="1" max="80" class="ops-input" /></label><label class="text-[11px] font-semibold">Next exam date<input v-model="form.examDate" type="date" class="ops-input" /></label><div class="sm:col-span-2 flex items-center justify-end gap-3 border-t border-border pt-4"><span v-if="saved" class="text-[12px] font-semibold text-mint">Goals saved</span><button class="ops-button-primary inline-flex min-w-28 items-center justify-center gap-2" :disabled="saving"><LoaderCircle v-if="saving" :size="14" class="animate-spin" /><Save v-else :size="14" />{{ saving ? 'Saving…' : 'Save goals' }}</button></div></section>
        <RouterLink to="/calendar" class="flex items-center gap-3 rounded-2xl border border-border bg-bg-card p-5 transition hover:border-blue-mid"><CalendarDays :size="18" class="text-blue" /><div><p class="text-[13px] font-semibold">Manage detailed schedule</p><p class="text-[12px] text-ink3">Add individual exams, study sessions and deadlines in Calendar.</p></div></RouterLink>
      </form>
    </div>
  </AppShell>
</template>
