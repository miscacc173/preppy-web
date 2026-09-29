<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { LoaderCircle, MonitorSmartphone, Pencil, Plus, Send, Trash2, X } from "lucide-vue-next";
import ConfirmModal from "@/components/operations/ConfirmModal.vue";
import DataPanel from "@/components/operations/DataPanel.vue";
import OpsModal from "@/components/operations/OpsModal.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import type { UiDeviceRecord, UiUserRecord } from "@/data/models/operations";

const loading = ref(true); const saving = ref(false); const sending = ref(false); const campaignMessage = ref(""); const deleting = ref(false); const showForm = ref(false); const error = ref(""); const devices = ref<UiDeviceRecord[]>([]); const users = ref<UiUserRecord[]>([]); const editingId = ref<number | null>(null); const deleteTarget = ref<UiDeviceRecord | null>(null);
const defaults = { user_id: 0, platform: "web", device_id: "", push_token: "", timezone: "UTC", is_active: true, metadata: {} }; const form = reactive({ ...defaults });
const campaign = reactive({ category: "announcement" as "promotion" | "announcement" | "system", audience: "all" as "all" | "role" | "user", role_slug: "student", user_id: 0, title: "", body: "", url: "/notifications" });
async function load() { loading.value = true; error.value = ""; try { const [devicePage, userPage] = await Promise.all([operationsRepository.devices({ per_page: 100 }), operationsRepository.users({ per_page: 100 })]); devices.value = devicePage.items; users.value = userPage.items; } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load system data."; } finally { loading.value = false; } }
function openCreate() { editingId.value = null; Object.assign(form, defaults); showForm.value = true; }
function openEdit(item: UiDeviceRecord) { editingId.value = item.id; Object.assign(form, { user_id: item.userId, platform: item.platform, device_id: item.deviceId, push_token: item.pushToken, timezone: item.timezone, is_active: item.active, metadata: {} }); showForm.value = true; }
async function save() { saving.value = true; error.value = ""; try { if (editingId.value) await operationsRepository.update("devices", editingId.value, form); else await operationsRepository.create("devices", form); showForm.value = false; await load(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to save device."; } finally { saving.value = false; } }
async function confirmRemove() { if (!deleteTarget.value) return; deleting.value = true; try { await operationsRepository.remove("devices", deleteTarget.value.id); deleteTarget.value = null; await load(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to remove device."; } finally { deleting.value = false; } }

async function sendCampaign() {
  sending.value = true; error.value = ""; campaignMessage.value = "";
  try {
    const response = await operationsRepository.sendPushCampaign({
      category: campaign.category,
      audience: campaign.audience,
      ...(campaign.audience === "role" ? { role_slug: campaign.role_slug } : {}),
      ...(campaign.audience === "user" ? { user_id: campaign.user_id } : {}),
      title: campaign.title, body: campaign.body, url: campaign.url || "/notifications",
    });
    const r = response.data;
    campaignMessage.value = `Processed ${r.users} users · ${r.sent} devices sent · ${r.skipped} skipped by preferences · ${r.failed} failed.`;
    campaign.title = ""; campaign.body = "";
  } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to send notification."; } finally { sending.value = false; }
}

const userName = (id: number) => users.value.find((user) => user.id === id)?.name || `User #${id}`; onMounted(load);
</script>

<template><div class="space-y-5"><div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-[12px] font-semibold uppercase tracking-[0.12em] text-blue">Notifications & delivery</p><h2 class="mt-1 text-[26px] font-bold tracking-[-0.035em]">System data</h2><p class="mt-1 text-[13px] text-ink3">Send platform notifications and manage registered web, iOS and Android delivery endpoints.</p></div><button class="ops-button-primary flex items-center gap-2 transition hover:-translate-y-0.5 hover:shadow-md" @click="openCreate"><Plus :size="15" /> Register device</button></div><p v-if="error" class="rounded-xl bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>
<DataPanel title="Send notification" description="Promotions respect marketing opt-in. Announcements and system notices are mandatory operational messages.">
  <form class="grid gap-3 p-5 lg:grid-cols-2" @submit.prevent="sendCampaign">
    <label class="text-[11px] font-semibold">Category<select v-model="campaign.category" class="ops-input"><option value="promotion">Promotion — opt-in only</option><option value="announcement">Announcement — mandatory</option><option value="system">System — mandatory</option></select></label>
    <label class="text-[11px] font-semibold">Audience<select v-model="campaign.audience" class="ops-input"><option value="all">All active users</option><option value="role">Users with role</option><option value="user">Specific user</option></select></label>
    <label v-if="campaign.audience === 'role'" class="text-[11px] font-semibold">Role<select v-model="campaign.role_slug" class="ops-input"><option value="student">Students</option><option value="parent">Parents</option><option value="teacher">Teachers</option><option value="tutor">Tutors</option><option value="institute_admin">Institute admins</option><option value="institute_staff">Institute staff</option></select></label>
    <label v-if="campaign.audience === 'user'" class="text-[11px] font-semibold">User<select v-model.number="campaign.user_id" required class="ops-input"><option :value="0" disabled>Select user</option><option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} — {{ user.email }}</option></select></label>
    <label class="text-[11px] font-semibold lg:col-span-2">Title<input v-model="campaign.title" maxlength="120" required class="ops-input" placeholder="Notification title" /></label>
    <label class="text-[11px] font-semibold lg:col-span-2">Message<textarea v-model="campaign.body" maxlength="500" required rows="3" class="ops-input" placeholder="Notification message"></textarea></label>
    <label class="text-[11px] font-semibold lg:col-span-2">Open URL<input v-model="campaign.url" class="ops-input" placeholder="/notifications" /></label>
    <div class="lg:col-span-2 flex items-center justify-between gap-3 flex-wrap"><p class="text-[11px] text-ink3">The same event fans out to every active web, iOS and Android device registered to each eligible user.</p><button class="ops-button-primary inline-flex items-center gap-2" :disabled="sending"><LoaderCircle v-if="sending" :size="14" class="animate-spin" /><Send v-else :size="14" />{{ sending ? 'Sending…' : 'Send notification' }}</button></div>
    <p v-if="campaignMessage" class="lg:col-span-2 rounded-xl bg-mint-light px-4 py-3 text-[12px] text-mint">{{ campaignMessage }}</p>
  </form>
</DataPanel>
<DataPanel title="Device registry" :description="`${devices.length} devices`" :loading="loading" :empty="!devices.length"><div class="overflow-x-auto"><table class="w-full min-w-[760px] text-left text-[12px]"><thead class="bg-bg-soft text-ink3"><tr><th class="px-5 py-3">Device</th><th class="px-4 py-3">User</th><th class="px-4 py-3">Platform</th><th class="px-4 py-3">Last seen</th><th class="px-4 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="item in devices" :key="item.id" class="transition hover:bg-bg-soft/70"><td class="px-5 py-3.5"><div class="flex items-center gap-2"><MonitorSmartphone :size="15" class="text-blue" /><span class="font-mono text-[11px]">{{ item.deviceId }}</span></div></td><td class="px-4 py-3.5 font-semibold">{{ userName(item.userId) }}</td><td class="px-4 py-3.5 capitalize">{{ item.platform }}</td><td class="px-4 py-3.5 text-ink3">{{ item.lastSeenAt ? new Date(item.lastSeenAt).toLocaleString() : 'Never' }}</td><td class="px-4 py-3.5"><StatusBadge :value="item.active ? 'active' : 'disabled'" /></td><td class="px-5 py-3.5"><div class="flex justify-end gap-1"><button class="ops-icon-button" title="Edit device" @click="openEdit(item)"><Pencil :size="14" /></button><button class="ops-icon-button hover:bg-pink-light hover:text-pink" title="Remove device" @click="deleteTarget = item"><Trash2 :size="14" /></button></div></td></tr></tbody></table></div></DataPanel>
  <OpsModal :open="showForm" max-width="max-w-md" :close-on-backdrop="!saving" @close="showForm = false"><form class="rounded-2xl border border-border bg-bg-card p-5 shadow-2xl" @submit.prevent="save"><div class="mb-5 flex justify-between"><div><h3 class="text-[17px] font-bold">{{ editingId ? 'Edit device' : 'Register device' }}</h3><p class="mt-1 text-[12px] text-ink3">Configure the notification delivery endpoint.</p></div><button type="button" :disabled="saving" @click="showForm = false"><X :size="18" /></button></div><div class="space-y-3"><label class="text-[11px] font-semibold">User<select v-model.number="form.user_id" required class="ops-input"><option :value="0" disabled>Select user</option><option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} — {{ user.email }}</option></select></label><label class="text-[11px] font-semibold">Platform<select v-model="form.platform" class="ops-input"><option value="web">Web</option><option value="ios">iOS</option><option value="android">Android</option></select></label><label class="text-[11px] font-semibold">Device ID<input v-model="form.device_id" required class="ops-input" /></label><label class="text-[11px] font-semibold">Push token<input v-model="form.push_token" class="ops-input" /></label><label class="text-[11px] font-semibold">Timezone<input v-model="form.timezone" class="ops-input" /></label><label class="flex items-center gap-2 text-[11px] font-semibold"><input v-model="form.is_active" type="checkbox" /> Active</label></div><div class="mt-5 flex justify-end gap-2"><button type="button" class="ops-button-secondary" :disabled="saving" @click="showForm = false">Cancel</button><button class="ops-button-primary inline-flex min-w-28 items-center justify-center gap-2" :disabled="saving"><LoaderCircle v-if="saving" :size="14" class="animate-spin" />{{ saving ? 'Saving…' : editingId ? 'Update device' : 'Register device' }}</button></div></form></OpsModal>
  <ConfirmModal :open="Boolean(deleteTarget)" title="Remove device?" :message="`Remove ${deleteTarget?.deviceId || 'this device'} from notification delivery?`" confirm-label="Remove device" :busy="deleting" @close="deleteTarget = null" @confirm="confirmRemove" />
</div></template>
