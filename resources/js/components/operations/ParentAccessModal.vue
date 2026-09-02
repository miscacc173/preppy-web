<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import { Copy, LoaderCircle, MailPlus, Trash2, X } from "lucide-vue-next";
import ConfirmModal from "@/components/operations/ConfirmModal.vue";
import OpsModal from "@/components/operations/OpsModal.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import type { UiParentAccessLink } from "@/data/models/operations";

const props = defineProps<{ open: boolean; student: { userId: number; name: string } | null }>();
const emit = defineEmits<{ close: [] }>();
const links = ref<UiParentAccessLink[]>([]);
const loading = ref(false);
const saving = ref(false);
const error = ref("");
const activationLink = ref("");
const revokeTarget = ref<UiParentAccessLink | null>(null);
const copied = ref(false);
const form = reactive({ parent_email: "", parent_name: "", relationship: "Parent", can_view_subscription: false });

async function load() {
  if (!props.open || !props.student) return;
  loading.value = true;
  error.value = "";
  try {
    links.value = await operationsRepository.parentLinks(props.student.userId);
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Unable to load parent access.";
  } finally {
    loading.value = false;
  }
}

async function invite() {
  if (!props.student) return;
  saving.value = true;
  error.value = "";
  try {
    const response = await operationsRepository.inviteParent(props.student.userId, { ...form });
    activationLink.value = `${window.location.origin}${response.activation_path}`;
    Object.assign(form, { parent_email: "", parent_name: "", relationship: "Parent", can_view_subscription: false });
    await load();
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Unable to invite parent.";
  } finally {
    saving.value = false;
  }
}

async function revoke() {
  if (!revokeTarget.value) return;
  saving.value = true;
  try {
    await operationsRepository.revokeParentLink(revokeTarget.value.id);
    revokeTarget.value = null;
    await load();
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Unable to revoke parent access.";
  } finally {
    saving.value = false;
  }
}

async function copyLink() {
  await window.navigator.clipboard.writeText(activationLink.value);
  copied.value = true;
  window.setTimeout(() => { copied.value = false; }, 1500);
}

function close() {
  activationLink.value = "";
  revokeTarget.value = null;
  emit("close");
}

watch(() => [props.open, props.student?.userId], load);
onMounted(load);
</script>

<template>
  <OpsModal :open="open" max-width="max-w-2xl" :close-on-backdrop="!saving" @close="close">
    <div class="max-h-[90vh] overflow-y-auto rounded-2xl border border-border bg-bg-card p-5 shadow-2xl">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-[17px] font-bold">Parent access</h3>
          <p class="mt-1 text-[12px] text-ink3">Link or revoke parents for {{ student?.name }}.</p>
        </div>
        <button :disabled="saving" @click="close"><X :size="18" /></button>
      </div>

      <p v-if="error" class="mt-4 rounded-xl bg-pink-light p-3 text-[12px] text-pink">{{ error }}</p>
      <div v-if="loading" class="mt-5 h-28 animate-pulse rounded-xl bg-bg-soft" />
      <div v-else class="mt-5 divide-y divide-border rounded-xl border border-border">
        <p v-if="!links.length" class="p-5 text-center text-[12px] text-ink3">No parent is linked to this student.</p>
        <div v-for="link in links" :key="link.id" class="flex items-center justify-between gap-3 p-3">
          <div class="min-w-0">
            <p class="truncate text-[12px] font-semibold">{{ link.parentName || 'Parent' }} · {{ link.relationship }}</p>
            <p class="truncate text-[11px] text-ink3">{{ link.parentEmail }}</p>
            <div class="mt-1 flex items-center gap-2"><StatusBadge :value="link.status" /><span class="text-[10px] text-ink4">{{ link.canViewSubscription ? 'Performance + subscription' : 'Performance only' }}</span></div>
          </div>
          <button class="ops-icon-button shrink-0 text-pink" title="Revoke parent access" @click="revokeTarget = link"><Trash2 :size="14" /></button>
        </div>
      </div>

      <div v-if="activationLink" class="mt-5 rounded-xl bg-mint-light p-4">
        <p class="text-[12px] font-semibold text-mint">Invitation emailed</p>
        <p class="mt-2 break-all font-mono text-[11px]">{{ activationLink }}</p>
        <button class="ops-button-secondary mt-3 inline-flex items-center gap-2" @click="copyLink"><Copy :size="14" />{{ copied ? 'Copied' : 'Copy activation link' }}</button>
      </div>

      <form v-else class="mt-5 rounded-xl bg-bg-soft p-4" @submit.prevent="invite">
        <div class="flex items-center gap-2"><MailPlus :size="15" class="text-blue" /><h4 class="text-[13px] font-bold">Link another parent</h4></div>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
          <label class="text-[11px] font-semibold">Parent email<input v-model="form.parent_email" required type="email" class="ops-input" /></label>
          <label class="text-[11px] font-semibold">Parent name<input v-model="form.parent_name" class="ops-input" /></label>
          <label class="text-[11px] font-semibold">Relationship<input v-model="form.relationship" class="ops-input" /></label>
          <label class="flex items-center gap-2 self-end pb-3 text-[11px] font-semibold"><input v-model="form.can_view_subscription" type="checkbox" /> Allow subscription visibility</label>
        </div>
        <div class="mt-4 flex justify-end"><button :disabled="saving" class="ops-button-primary inline-flex items-center gap-2"><LoaderCircle v-if="saving" :size="14" class="animate-spin" />{{ saving ? 'Sending…' : 'Send parent invitation' }}</button></div>
      </form>
    </div>
  </OpsModal>

  <ConfirmModal :open="Boolean(revokeTarget)" title="Revoke parent access?" :message="`Remove ${revokeTarget?.parentName || 'this parent'} from ${student?.name || 'this student'}?`" confirm-label="Revoke access" :busy="saving" tone="danger" @close="revokeTarget = null" @confirm="revoke" />
</template>
