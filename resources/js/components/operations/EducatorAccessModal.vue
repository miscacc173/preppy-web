<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { LoaderCircle, Trash2, X } from "lucide-vue-next";
import OpsModal from "@/components/operations/OpsModal.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiAccessGrant, UiStudentRecord, UiTeachingClass } from "@/data/models/operations";
import type { UiCatalog } from "@/data/models/platform";

const props = withDefaults(defineProps<{ open: boolean; educator: { id: number; name: string } | null; instituteId?: number | null; allowParentManagement?: boolean }>(), { allowParentManagement: false });
const emit = defineEmits<{ close: [] }>();
const loading = ref(false); const saving = ref(false); const error = ref(""); const grants = ref<UiAccessGrant[]>([]); const students = ref<UiStudentRecord[]>([]); const classes = ref<UiTeachingClass[]>([]); const catalog = ref<UiCatalog | null>(null);
const form = reactive({ scope_type: "student" as UiAccessGrant["scopeType"], scope_id: 0, can_manage_parents: false });
const options = computed(() => form.scope_type === "student" ? students.value.map((item) => ({ id: item.userId, name: item.name })) : form.scope_type === "class" ? classes.value.map((item) => ({ id: item.id, name: item.name })) : form.scope_type === "course" ? (catalog.value?.courses || []) : (catalog.value?.subjects || []));
const scopeLabel = (grant: UiAccessGrant) => optionsFor(grant.scopeType).find((item) => item.id === grant.scopeId)?.name || `${grant.scopeType} #${grant.scopeId}`;
const optionsFor = (type: UiAccessGrant["scopeType"]) => type === "student" ? students.value.map((item) => ({ id: item.userId, name: item.name })) : type === "class" ? classes.value : type === "course" ? (catalog.value?.courses || []) : (catalog.value?.subjects || []);
async function load() { if (!props.open || !props.educator) return; loading.value = true; error.value = ""; try { const tasks: Promise<unknown>[] = [operationsRepository.educatorGrants(props.educator.id, props.instituteId || undefined), platformRepository.catalog()]; if (props.instituteId) tasks.push(operationsRepository.students({ institute_id: props.instituteId, per_page: 100 }), operationsRepository.teachingClasses(props.instituteId)); const [grantData, catalogData, studentPage, classData] = await Promise.all(tasks); grants.value = grantData as UiAccessGrant[]; catalog.value = catalogData as UiCatalog; students.value = (studentPage as { items?: UiStudentRecord[] } | undefined)?.items || []; classes.value = (classData as UiTeachingClass[] | undefined) || []; } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load access grants."; } finally { loading.value = false; } }
async function grant() { if (!props.educator || !form.scope_id) return; saving.value = true; error.value = ""; try { await operationsRepository.grantEducatorAccess(props.educator.id, { institute_id: props.instituteId || null, scope_type: form.scope_type, scope_id: form.scope_id, can_manage_parents: props.allowParentManagement && form.can_manage_parents }); form.scope_id = 0; form.can_manage_parents = false; await load(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to grant access."; } finally { saving.value = false; } }
async function revoke(grant: UiAccessGrant) { if (!props.educator) return; saving.value = true; try { await operationsRepository.revokeEducatorAccess(props.educator.id, grant.id); await load(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to revoke access."; } finally { saving.value = false; } }
watch(() => [props.open, props.educator?.id, props.instituteId], load); watch(() => form.scope_type, () => { form.scope_id = 0; }); onMounted(load);
</script>
<template>
  <OpsModal :open="open" max-width="max-w-xl" :close-on-backdrop="!saving" @close="emit('close')">
    <div class="rounded-2xl border border-border bg-bg-card p-5 shadow-2xl">
      <div class="flex items-start justify-between">
        <div><h3 class="text-[17px] font-bold">Scoped educator access</h3><p class="mt-1 text-[12px] text-ink3">{{ educator?.name }} can only see data covered by active grants.</p></div>
        <button :disabled="saving" @click="emit('close')"><X :size="18" /></button>
      </div>
      <p v-if="error" class="mt-4 rounded-xl bg-pink-light p-3 text-[12px] text-pink">{{ error }}</p>
      <div v-if="loading" class="mt-5 h-32 animate-pulse rounded-xl bg-bg-soft" />
      <template v-else>
        <form class="mt-5 space-y-3" @submit.prevent="grant">
          <div class="grid gap-3 sm:grid-cols-[140px_1fr_auto]">
            <select v-model="form.scope_type" class="ops-input"><option value="student">Student</option><option value="class" :disabled="!instituteId">Class</option><option value="course">Course</option><option value="subject">Subject</option></select>
            <select v-model.number="form.scope_id" required class="ops-input"><option :value="0" disabled>Select {{ form.scope_type }}</option><option v-for="item in options" :key="item.id" :value="item.id">{{ item.name }}</option></select>
            <button class="ops-button-primary inline-flex items-center justify-center gap-2" :disabled="saving || !form.scope_id"><LoaderCircle v-if="saving" :size="14" class="animate-spin" />Grant</button>
          </div>
          <label v-if="allowParentManagement" class="flex items-center gap-2 rounded-xl bg-bg-soft px-3 py-2 text-[11px] font-semibold"><input v-model="form.can_manage_parents" type="checkbox" /> Allow this teacher to link and revoke parents for students covered by this grant</label>
        </form>
        <div class="mt-5 divide-y divide-border rounded-xl border border-border">
          <div v-if="!grants.length" class="p-5 text-center text-[12px] text-ink3">No grants yet. This educator cannot view student data.</div>
          <div v-for="grantItem in grants" :key="grantItem.id" class="flex items-center justify-between p-3 text-[12px]">
            <div><p class="font-semibold capitalize">{{ grantItem.scopeType }} · {{ scopeLabel(grantItem) }}</p><div class="mt-1 flex items-center gap-2"><StatusBadge :value="grantItem.status" /><span v-if="grantItem.canManageParents" class="rounded-full bg-blue-light px-2 py-0.5 text-[10px] font-semibold text-blue">Parent management</span></div></div>
            <button v-if="grantItem.status === 'active'" class="ops-icon-button text-pink" title="Revoke grant" :disabled="saving" @click="revoke(grantItem)"><Trash2 :size="14" /></button>
          </div>
        </div>
      </template>
    </div>
  </OpsModal>
</template>
