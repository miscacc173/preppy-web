<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { Bell, Check, Settings2 } from "lucide-vue-next";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { studentWorkspaceRepository } from "@/data/repositories/studentWorkspaceRepository";
import type { UiStudentNotification } from "@/data/models/studentWorkspace";

const open = ref(false); const items = ref<UiStudentNotification[]>([]); const loading = ref(false);
const unread = computed(() => items.value.filter((item) => !item.read).length);
async function load() { loading.value = true; try { items.value = await studentWorkspaceRepository.notifications(); } finally { loading.value = false; } }
async function markAll() { items.value = await studentWorkspaceRepository.saveNotifications(items.value.map((item) => ({ ...item, read: true }))); }
async function markRead(item: UiStudentNotification) { if (!item.read) items.value = await studentWorkspaceRepository.saveNotifications(items.value.map((value) => value.id === item.id ? { ...value, read: true } : value)); open.value = false; }
watch(open, (value) => { if (value) void load(); }); onMounted(load);
</script>

<template>
  <Popover v-model:open="open"><PopoverTrigger as-child><button aria-label="Notifications" class="relative grid h-11 w-11 place-items-center rounded-md text-ink2 hover:bg-bg-soft"><Bell :size="18" /><span v-if="unread" class="absolute right-2.5 top-2.5 h-1.5 w-1.5 rounded-full bg-pink" /></button></PopoverTrigger><PopoverContent align="end" :side-offset="8" class="w-[360px] overflow-hidden rounded-2xl border border-border bg-bg-card p-0 shadow-xl"><div class="flex items-center justify-between border-b border-border px-4 py-3"><div><p class="text-[14px] font-semibold">Notifications</p><p class="mt-0.5 text-[11px] text-ink3">{{ unread ? `${unread} unread` : 'All caught up' }}</p></div><div class="flex items-center gap-1"><button v-if="unread" class="ops-icon-button" title="Mark all read" @click="markAll"><Check :size="13" /></button><RouterLink to="/settings" class="ops-icon-button" title="Notification settings" @click="open = false"><Settings2 :size="13" /></RouterLink></div></div><div class="max-h-[420px] overflow-y-auto"><div v-if="loading" class="space-y-2 p-4"><div v-for="item in 3" :key="item" class="h-16 animate-pulse rounded-xl bg-bg-soft" /></div><div v-else-if="items.length" class="divide-y divide-border"><RouterLink v-for="item in items.slice(0, 8)" :key="item.id" :to="item.to || '/notifications'" class="block px-4 py-3 transition hover:bg-bg-soft" @click="markRead(item)"><div class="flex items-start gap-2"><span v-if="!item.read" class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue" /><div><p class="text-[12px] font-semibold text-ink">{{ item.title }}</p><p class="mt-0.5 line-clamp-2 text-[11px] leading-4 text-ink3">{{ item.body }}</p></div></div></RouterLink></div><div v-else class="p-8 text-center text-[12px] text-ink3">No notifications yet.</div></div><RouterLink to="/notifications" class="block border-t border-border px-4 py-3 text-center text-[12px] font-semibold text-blue" @click="open = false">View all notifications</RouterLink></PopoverContent></Popover>
</template>
