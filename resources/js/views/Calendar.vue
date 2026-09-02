<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { CalendarDays, Clock, LoaderCircle, Plus, Trash2, X } from "lucide-vue-next";
import AppShell from "@/components/app/AppShell.vue";
import ConfirmModal from "@/components/operations/ConfirmModal.vue";
import OpsModal from "@/components/operations/OpsModal.vue";
import { studentWorkspaceRepository } from "@/data/repositories/studentWorkspaceRepository";
import type { UiCalendarEvent } from "@/data/models/studentWorkspace";
import { setPageMeta } from "@/lib/useRouteHead";

setPageMeta({ title: "Calendar & Exam Dates — AiStar Prep" });
const events = ref<UiCalendarEvent[]>([]); const loading = ref(true); const saving = ref(false); const showForm = ref(false); const error = ref(""); const deleteTarget = ref<UiCalendarEvent | null>(null);
const form = reactive({ title: "", date: "", time: "", type: "study" as UiCalendarEvent["type"] });
const sorted = computed(() => [...events.value].sort((a, b) => `${a.date} ${a.time}`.localeCompare(`${b.date} ${b.time}`)));
async function load() { loading.value = true; try { events.value = await studentWorkspaceRepository.calendar(); } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load calendar."; } finally { loading.value = false; } }
function openCreate() { Object.assign(form, { title: "", date: "", time: "", type: "study" }); showForm.value = true; }
async function addEvent() { saving.value = true; error.value = ""; try { const event: UiCalendarEvent = { ...form, id: `${Date.now()}` }; events.value = await studentWorkspaceRepository.saveCalendar([...events.value, event]); await studentWorkspaceRepository.notify({ title: "Calendar updated", body: `${event.title} was added to your calendar.`, category: event.type === "exam" ? "exams" : "study", to: "/calendar" }); showForm.value = false; } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to save event."; } finally { saving.value = false; } }
async function removeEvent() { if (!deleteTarget.value) return; saving.value = true; try { events.value = await studentWorkspaceRepository.saveCalendar(events.value.filter((item) => item.id !== deleteTarget.value?.id)); deleteTarget.value = null; } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to remove event."; } finally { saving.value = false; } }
onMounted(load);
</script>

<template>
  <AppShell title="Calendar & Exam Dates" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="space-y-5">
      <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="label-mono">Your schedule</p><h2 class="mt-1 text-[26px] font-bold tracking-[-0.03em]">Calendar</h2><p class="mt-1 text-[13px] text-ink3">Exam dates, study sessions and deadlines saved to your account.</p></div><button class="ops-button-primary inline-flex items-center gap-2" @click="openCreate"><Plus :size="15" /> Add event</button></div>
      <p v-if="error" class="rounded-xl bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>
      <div v-if="loading" class="grid gap-3 md:grid-cols-2"><div v-for="item in 4" :key="item" class="h-28 animate-pulse rounded-2xl bg-bg-soft" /></div>
      <div v-else-if="sorted.length" class="grid gap-4 md:grid-cols-2"><article v-for="event in sorted" :key="event.id" class="rounded-2xl border border-border bg-bg-card p-5 transition hover:-translate-y-0.5 hover:shadow-md"><div class="flex items-start justify-between gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-light text-blue"><CalendarDays :size="18" /></span><button class="ops-icon-button hover:bg-pink-light hover:text-pink" title="Delete event" @click="deleteTarget = event"><Trash2 :size="14" /></button></div><p class="mt-4 text-[15px] font-semibold text-ink">{{ event.title }}</p><p class="mt-1 text-[12px] capitalize text-ink3">{{ event.type }}</p><div class="mt-4 flex gap-4 text-[12px] text-ink2"><span>{{ new Date(`${event.date}T00:00:00`).toLocaleDateString() }}</span><span v-if="event.time" class="inline-flex items-center gap-1"><Clock :size="12" />{{ event.time }}</span></div></article></div>
      <div v-else class="rounded-2xl border border-border bg-bg-card p-10 text-center"><CalendarDays :size="28" class="mx-auto text-ink4" /><p class="mt-3 text-[15px] font-semibold">No calendar events yet</p><p class="mt-1 text-[13px] text-ink3">Add your first exam, study session or deadline.</p></div>
    </div>
    <OpsModal :open="showForm" max-width="max-w-md" :close-on-backdrop="!saving" @close="showForm = false"><form class="rounded-2xl border border-border bg-bg-card p-5 shadow-2xl" @submit.prevent="addEvent"><div class="mb-5 flex justify-between"><div><h3 class="text-[17px] font-bold">Add calendar event</h3><p class="mt-1 text-[12px] text-ink3">This event is stored against your account.</p></div><button type="button" :disabled="saving" @click="showForm = false"><X :size="18" /></button></div><div class="space-y-3"><label class="text-[11px] font-semibold">Title<input v-model="form.title" required class="ops-input" /></label><label class="text-[11px] font-semibold">Type<select v-model="form.type" class="ops-input"><option value="exam">Exam</option><option value="study">Study</option><option value="deadline">Deadline</option></select></label><label class="text-[11px] font-semibold">Date<input v-model="form.date" required type="date" class="ops-input" /></label><label class="text-[11px] font-semibold">Time<input v-model="form.time" type="time" class="ops-input" /></label></div><div class="mt-5 flex justify-end gap-2"><button type="button" class="ops-button-secondary" :disabled="saving" @click="showForm = false">Cancel</button><button class="ops-button-primary inline-flex min-w-28 items-center justify-center gap-2" :disabled="saving"><LoaderCircle v-if="saving" :size="14" class="animate-spin" />{{ saving ? 'Saving…' : 'Add event' }}</button></div></form></OpsModal>
    <ConfirmModal :open="Boolean(deleteTarget)" title="Delete calendar event?" :message="`Remove ${deleteTarget?.title || 'this event'} from your calendar?`" confirm-label="Delete event" :busy="saving" @close="deleteTarget = null" @confirm="removeEvent" />
  </AppShell>
</template>
