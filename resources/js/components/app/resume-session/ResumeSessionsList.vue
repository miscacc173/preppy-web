<script setup lang="ts">
import { useInProgressSessions } from "@/lib/learnSessionStore";
import SessionRow from "./SessionRow.vue";

withDefaults(
  defineProps<{ compact?: boolean; emptyHidden?: boolean; title?: string }>(),
  { compact: false, emptyHidden: true, title: "Resume unfinished sessions" },
);

const sessions = useInProgressSessions();
</script>

<template>
  <div v-if="sessions.length === 0 && !emptyHidden" class="bg-bg-card border border-dashed border-border rounded-2xl p-6 text-center">
    <p class="text-[13px] text-ink3">No unfinished sessions. Start a new one to see it here.</p>
  </div>
  <div v-else-if="sessions.length > 0">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-[14px] font-semibold text-ink">{{ title }}</h3>
      <span class="font-mono text-[11px] text-ink3">{{ sessions.length }} active</span>
    </div>
    <div :class="compact ? 'space-y-2.5' : 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4'">
      <SessionRow v-for="s in sessions" :key="s.id" :s="s" :compact="compact" />
    </div>
  </div>
</template>
