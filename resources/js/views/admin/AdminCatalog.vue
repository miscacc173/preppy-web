<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { LoaderCircle, Pencil, Plus, Trash2, X } from "lucide-vue-next";
import ConfirmModal from "@/components/operations/ConfirmModal.vue";
import DataPanel from "@/components/operations/DataPanel.vue";
import OpsModal from "@/components/operations/OpsModal.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import type { UiCatalogRecord } from "@/data/models/operations";

type Tab = "exam-boards" | "courses" | "subjects" | "topics";
const tabs: Array<{ key: Tab; label: string }> = [
  { key: "exam-boards", label: "Exam boards" }, { key: "courses", label: "Courses" },
  { key: "subjects", label: "Subjects" }, { key: "topics", label: "Topics" },
];
const tab = ref<Tab>("exam-boards");
const rows = ref<UiCatalogRecord[]>([]); const boards = ref<UiCatalogRecord[]>([]); const subjects = ref<UiCatalogRecord[]>([]);
const loading = ref(false); const saving = ref(false); const deleting = ref(false); const showForm = ref(false); const error = ref("");
const editingId = ref<number | null>(null); const deleteTarget = ref<UiCatalogRecord | null>(null);
const defaults = { name: "", code: "", level: "O Level", qualification_family: "O Level", board_ids: [] as number[], subject_id: "", subject_ids: [] as number[], display_order: 0, status: "active", metadata: {} };
const form = reactive({ ...defaults });
const title = computed(() => tabs.find((item) => item.key === tab.value)?.label || "Catalog");

async function load() {
  loading.value = true; error.value = "";
  try {
    const loader = tab.value === "exam-boards" ? operationsRepository.examBoards : tab.value === "courses" ? operationsRepository.courses : tab.value === "subjects" ? operationsRepository.subjects : operationsRepository.topics;
    const [page, boardPage, subjectPage] = await Promise.all([loader({ per_page: 100 }), operationsRepository.examBoards({ per_page: 100 }), operationsRepository.subjects({ per_page: 100 })]);
    rows.value = page.items; boards.value = boardPage.items; subjects.value = subjectPage.items;
  } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load catalog."; }
  finally { loading.value = false; }
}
function openCreate() { editingId.value = null; Object.assign(form, { ...defaults, board_ids: [], subject_ids: [] }); showForm.value = true; }
function openEdit(item: UiCatalogRecord) {
  editingId.value = item.id;
  Object.assign(form, { ...defaults, name: item.name, code: item.code, level: item.detail || "O Level", qualification_family: item.qualificationFamily || item.detail || "O Level", board_ids: item.boardIds.length ? [...item.boardIds] : item.examBoardId ? [item.examBoardId] : [], subject_id: item.subjectId ? String(item.subjectId) : "", subject_ids: [...item.subjectIds], display_order: item.displayOrder, status: item.status });
  showForm.value = true;
}
function payload() {
  const availability = { status: form.status, is_active: form.status !== "inactive" };
  if (tab.value === "exam-boards") return { name: form.name, code: form.code, ...availability, metadata: {} };
  if (tab.value === "courses") return { name: form.name, code: form.code, level: form.level, qualification_family: form.qualification_family, board_ids: form.board_ids, start_year: null, end_year: null, ...availability, metadata: {}, subject_ids: form.subject_ids };
  if (tab.value === "subjects") return { name: form.name, code: form.code, syllabus_year_start: null, syllabus_year_end: null, status: form.status, metadata: {} };
  return { name: form.name, code: form.code, subject_id: Number(form.subject_id), display_order: form.display_order, status: form.status, metadata: {} };
}
async function saveRecord() { saving.value = true; error.value = ""; try { if (editingId.value) await operationsRepository.update(tab.value, editingId.value, payload()); else await operationsRepository.create(tab.value, payload()); showForm.value = false; await load(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to save catalog record."; } finally { saving.value = false; } }
async function confirmRemove() { if (!deleteTarget.value) return; deleting.value = true; try { await operationsRepository.remove(tab.value, deleteTarget.value.id); deleteTarget.value = null; await load(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to delete record."; } finally { deleting.value = false; } }
watch(tab, () => { showForm.value = false; deleteTarget.value = null; load(); }); onMounted(load);
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-[12px] font-semibold uppercase tracking-[0.12em] text-gold">Learning structure</p><h2 class="mt-1 text-[26px] font-bold tracking-[-0.035em]">Academic catalog</h2><p class="mt-1 text-[13px] text-ink3">Manage boards, courses, subjects and their allowed relationships.</p></div><button class="ops-button-primary flex items-center gap-2" @click="openCreate"><Plus :size="15" /> Add {{ title.toLowerCase().replace(/s$/, '') }}</button></div>
    <p v-if="error" class="rounded-xl bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>
    <div class="flex gap-1 rounded-xl border border-border bg-bg-card p-1.5"><button v-for="item in tabs" :key="item.key" class="rounded-lg px-4 py-2 text-[12px] font-semibold" :class="tab === item.key ? 'bg-blue text-white' : 'text-ink3 hover:bg-bg-soft'" @click="tab = item.key">{{ item.label }}</button></div>
    <DataPanel :title="title" :description="`${rows.length} records`" :loading="loading" :empty="!rows.length"><div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left text-[12px]"><thead class="bg-bg-soft text-ink3"><tr><th class="px-5 py-3">Name</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Type / level</th><th class="px-4 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="item in rows" :key="item.id"><td class="px-5 py-3.5 font-semibold">{{ item.name }}</td><td class="px-4 py-3.5 font-mono">{{ item.code }}</td><td class="px-4 py-3.5">{{ item.detail || '—' }}</td><td class="px-4 py-3.5"><StatusBadge :value="item.status" /></td><td class="px-5 py-3.5"><div class="flex justify-end gap-1"><button class="ops-icon-button" title="Edit record" @click="openEdit(item)"><Pencil :size="14" /></button><button class="ops-icon-button hover:bg-pink-light hover:text-pink" title="Delete record" @click="deleteTarget = item"><Trash2 :size="14" /></button></div></td></tr></tbody></table></div></DataPanel>
    <OpsModal :open="showForm" max-width="max-w-lg" :close-on-backdrop="!saving" @close="showForm = false"><form class="max-h-[90vh] overflow-y-auto rounded-2xl border border-border bg-bg-card p-5 shadow-2xl" @submit.prevent="saveRecord">
      <div class="mb-5 flex justify-between"><div><h3 class="text-[17px] font-bold">{{ editingId ? 'Edit' : 'Add' }} {{ title.toLowerCase().replace(/s$/, '') }}</h3><p class="mt-1 text-[12px] text-ink3">These relationships control signup, guest mode and learning-path setup.</p></div><button type="button" :disabled="saving" @click="showForm = false"><X :size="18" /></button></div>
      <div class="space-y-3"><label class="text-[11px] font-semibold">Name<input v-model="form.name" required class="ops-input" /></label><label class="text-[11px] font-semibold">Code<input v-model="form.code" required class="ops-input uppercase" /></label>
        <template v-if="tab === 'courses'"><label class="text-[11px] font-semibold">Level<input v-model="form.level" required class="ops-input" placeholder="O Level, IGCSE, AS Level…" /></label><label class="text-[11px] font-semibold">Qualification family<input v-model="form.qualification_family" required class="ops-input" /></label><fieldset><legend class="mb-2 text-[11px] font-semibold">Allowed exam boards</legend><div class="grid max-h-36 gap-2 overflow-y-auto rounded-xl border border-border p-3 sm:grid-cols-2"><label v-for="boardItem in boards" :key="boardItem.id" class="flex items-center gap-2 text-[11px]"><input v-model="form.board_ids" type="checkbox" :value="boardItem.id" />{{ boardItem.name }}</label></div></fieldset><fieldset><legend class="mb-2 text-[11px] font-semibold">Available subjects</legend><div class="grid max-h-44 gap-2 overflow-y-auto rounded-xl border border-border p-3 sm:grid-cols-2"><label v-for="subjectItem in subjects" :key="subjectItem.id" class="flex items-center gap-2 text-[11px]"><input v-model="form.subject_ids" type="checkbox" :value="subjectItem.id" />{{ subjectItem.name }}</label></div></fieldset></template>
        <template v-if="tab === 'topics'"><label class="text-[11px] font-semibold">Subject<select v-model="form.subject_id" required class="ops-input"><option value="" disabled>Select subject</option><option v-for="subjectItem in subjects" :key="subjectItem.id" :value="subjectItem.id">{{ subjectItem.name }}</option></select></label><label class="text-[11px] font-semibold">Display order<input v-model.number="form.display_order" type="number" min="0" class="ops-input" /></label></template>
        <label class="text-[11px] font-semibold">Availability<select v-model="form.status" class="ops-input"><option value="active">Active</option><option value="coming_soon">Coming soon</option><option value="inactive">Inactive / hidden</option></select></label></div>
      <div class="mt-5 flex justify-end gap-2"><button type="button" :disabled="saving" class="ops-button-secondary" @click="showForm = false">Cancel</button><button :disabled="saving || (tab === 'courses' && (!form.board_ids.length || !form.subject_ids.length))" class="ops-button-primary inline-flex min-w-28 items-center justify-center gap-2"><LoaderCircle v-if="saving" :size="14" class="animate-spin" />{{ saving ? 'Saving…' : editingId ? 'Update' : 'Create' }}</button></div>
    </form></OpsModal>
    <ConfirmModal :open="Boolean(deleteTarget)" :title="`Delete ${title.toLowerCase().replace(/s$/, '')}?`" :message="`Remove ${deleteTarget?.name || 'this record'} from the catalog? Related records may prevent deletion.`" confirm-label="Delete record" :busy="deleting" @close="deleteTarget = null" @confirm="confirmRemove" />
  </div>
</template>
