<script setup lang="ts">
import { computed } from "vue";
import { Flame, PlayCircle, Target, Trophy } from "lucide-vue-next";
import type { UiAccount } from "@/data/models/platform";
import { useInProgressSessions } from "@/lib/learnSessionStore";
import { useSessions } from "@/lib/sessionStore";
import Stat from "./Stat.vue";

defineProps<{ account: UiAccount | null }>();
const sessions = useSessions(); const inProgress = useInProgressSessions();
const average = computed(() => sessions.value.length ? Math.round(sessions.value.reduce((sum, item) => sum + item.accuracy, 0) / sessions.value.length) : 0);
const achievements = computed(() => [sessions.value.length >= 1 ? "First session" : "", sessions.value.length >= 10 ? "10 sessions" : "", average.value >= 80 ? "80% average" : ""].filter(Boolean));
</script>

<template>
  <div class="rounded-xl border border-border bg-bg-card p-5"><div class="flex items-center gap-3"><div class="grid h-12 w-12 place-items-center rounded-full bg-blue font-semibold text-white">{{ (account?.name || 'S').split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() }}</div><div><p class="text-[14px] font-semibold">{{ account?.name || 'Student' }}</p><p class="text-[12px] text-ink3">{{ account?.course?.name || 'Course not selected' }} · {{ account?.examBoard?.name || 'Board not selected' }}</p></div></div><div class="mt-5 grid grid-cols-3 gap-2"><Stat label="Sessions"><template #icon><Flame :size="14" class="text-pink" /></template>{{ sessions.length }}</Stat><Stat label="Average"><template #icon><Trophy :size="14" class="text-gold" /></template>{{ average }}%</Stat><Stat label="Active"><template #icon><Target :size="14" class="text-mint" /></template>{{ inProgress.length }}</Stat></div></div>
  <div class="rounded-xl border border-border bg-bg-card p-5"><div class="mb-3 flex items-center justify-between"><p class="label-mono">Resume sessions</p><span class="text-[10px] text-ink4">{{ inProgress.length }} active</span></div><ul v-if="inProgress.length" class="space-y-2"><li v-for="item in inProgress.slice(0, 3)" :key="item.id" class="flex items-center gap-3 rounded-lg border border-border bg-bg-soft p-3"><PlayCircle :size="16" class="text-blue" /><div class="min-w-0 flex-1"><p class="truncate text-[12px] font-semibold">{{ item.subject }} · {{ item.kind }}</p><p class="truncate text-[11px] text-ink4">Question {{ item.qIdx + 1 }} of {{ item.questions.length }}</p></div><RouterLink :to="{ path: '/learn/session', query: { resume: '1', sid: item.id } }" class="text-[11px] font-semibold text-blue">Resume</RouterLink></li></ul><p v-else class="text-[12px] text-ink3">No sessions waiting to resume.</p></div>
  <div class="rounded-xl border border-border bg-bg-card p-5"><p class="label-mono mb-3">Achievements</p><div v-if="achievements.length" class="flex flex-wrap gap-2"><span v-for="item in achievements" :key="item" class="rounded-md border border-border bg-bg-soft px-2.5 py-1.5 text-[11px]">{{ item }}</span></div><p v-else class="text-[12px] text-ink3">Complete sessions to unlock achievements.</p></div>
</template>
