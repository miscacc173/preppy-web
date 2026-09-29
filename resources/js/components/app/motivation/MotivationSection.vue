<script setup lang="ts">
// Compact motivation widgets — reuse the existing dashboard tokens
// (bg-bg-card, border-border, rounded-xl, label-mono, ink scale) so they
// feel native to the AI Star Prep dashboard.
import { computed, onMounted, ref } from "vue";
import { Flame, Users, TrendingUp, Sparkles, Trophy, CheckCircle2 } from "lucide-vue-next";
import { useSessions } from "@/lib/sessionStore";
import { computeAchievements, computeCommunity, computeMotivation, computeRanking, computeWeekly } from "@/lib/motivation";

const props = defineProps<{ readiness: number; grade: string; subjectName: string }>();

function formatMinutes(m: number) {
  if (m < 60) return `${m}m`;
  const h = Math.floor(m / 60);
  const rem = m % 60;
  return rem ? `${h}h ${rem}m` : `${h}h`;
}

function nf(n: number) {
  return n.toLocaleString();
}

const INITIAL_COMMUNITY = { studying: 1847, mocks: 124, questions: 5234 };
const community = ref(INITIAL_COMMUNITY);
onMounted(() => {
  community.value = computeCommunity();
});

const sessions = useSessions();

const weekly = computed(() => computeWeekly(sessions.value));
const achievements = computed(() => computeAchievements(sessions.value));
const ranking = computed(() => computeRanking(props.readiness, weekly.value.sessions));
const motivation = computed(() =>
  computeMotivation({
    readiness: props.readiness,
    grade: props.grade,
    weekly: weekly.value,
    achievementsCount: achievements.value.length,
    sessions: sessions.value,
    subjectName: props.subjectName,
  }),
);
</script>

<template>
  <section class="mt-7 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Achievements -->
    <div class="bg-bg-card border border-border rounded-xl p-5">
      <div class="flex items-center justify-between mb-3">
        <p class="label-mono">Achievements</p>
        <Trophy :size="14" class="text-gold" />
      </div>
      <p v-if="achievements.length === 0" class="text-[12px] text-ink3">Complete your first session to unlock badges.</p>
      <ul v-else class="flex flex-col gap-1.5">
        <li v-for="a in achievements.slice(0, 4)" :key="a.id" class="flex items-center gap-2 bg-bg-soft border border-border rounded-md px-2.5 py-1.5">
          <span class="text-[14px] leading-none">{{ a.emoji }}</span>
          <span class="text-[12px] text-ink2 font-medium truncate">{{ a.label }}</span>
        </li>
      </ul>
    </div>

    <!-- Weekly progress -->
    <div class="bg-bg-card border border-border rounded-xl p-5">
      <div class="flex items-center justify-between mb-3">
        <p class="label-mono">This week</p>
        <Flame :size="14" class="text-pink" />
      </div>
      <div class="grid grid-cols-3 gap-2 mb-3">
        <div class="bg-bg-soft rounded-md p-2 text-center">
          <p class="font-mono text-[15px] font-medium text-ink leading-none">{{ weekly.sessions }}</p>
          <p class="text-[10px] text-ink4 mt-1">Sessions</p>
        </div>
        <div class="bg-bg-soft rounded-md p-2 text-center">
          <p class="font-mono text-[15px] font-medium text-ink leading-none">{{ weekly.questions }}</p>
          <p class="text-[10px] text-ink4 mt-1">Questions</p>
        </div>
        <div class="bg-bg-soft rounded-md p-2 text-center">
          <p class="font-mono text-[15px] font-medium text-ink leading-none">{{ formatMinutes(weekly.minutes) }}</p>
          <p class="text-[10px] text-ink4 mt-1">Time</p>
        </div>
      </div>
      <div class="flex items-center justify-between text-[11px] text-ink3 mb-1">
        <span>Weekly goal</span>
        <span class="font-mono">{{ weekly.sessions }}/{{ weekly.goal }}</span>
      </div>
      <div class="h-1.5 bg-bg-soft2 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-[width] duration-700" :style="{ width: `${weekly.goalPct}%`, background: 'var(--mint)' }" />
      </div>
      <div class="flex items-center gap-1.5 mt-3 text-[11px] text-ink3">
        <CheckCircle2 :size="12" class="text-mint" />
        {{ weekly.streak > 0 ? `${weekly.streak}-day learning streak` : "Start a session to begin a streak" }}
      </div>
    </div>

    <!-- Community activity -->
    <div class="bg-bg-card border border-border rounded-xl p-5">
      <div class="flex items-center justify-between mb-3">
        <p class="label-mono">Today on AI Star</p>
        <Users :size="14" class="text-blue" />
      </div>
      <ul class="flex flex-col gap-2.5">
        <li class="flex items-center gap-2.5">
          <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: var(--blue)" />
          <span class="font-mono text-[13px] text-ink font-medium">{{ nf(community.studying) }}</span>
          <span class="text-[12px] text-ink3 truncate">students studying</span>
        </li>
        <li class="flex items-center gap-2.5">
          <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: var(--gold)" />
          <span class="font-mono text-[13px] text-ink font-medium">{{ nf(community.mocks) }}</span>
          <span class="text-[12px] text-ink3 truncate">mock exams completed</span>
        </li>
        <li class="flex items-center gap-2.5">
          <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: var(--mint)" />
          <span class="font-mono text-[13px] text-ink font-medium">{{ nf(community.questions) }}</span>
          <span class="text-[12px] text-ink3 truncate">questions solved</span>
        </li>
      </ul>
    </div>

    <!-- Ranking -->
    <div class="bg-bg-card border border-border rounded-xl p-5">
      <div class="flex items-center justify-between mb-3">
        <p class="label-mono">Your ranking</p>
        <TrendingUp :size="14" class="text-violet" />
      </div>
      <p class="font-mono text-[26px] font-medium text-ink leading-none">Top {{ ranking.percentile }}%</p>
      <p class="text-[12px] text-ink3 mt-1.5">Rank #{{ ranking.rank }} this week</p>
      <div class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-medium rounded-full px-2 py-0.5 bg-mint-light text-mint">
        <TrendingUp :size="11" />
        {{ ranking.delta >= 0 ? `+${ranking.delta}` : ranking.delta }} positions
      </div>
    </div>

    <!-- Motivation — full width, subtle -->
    <div class="md:col-span-2 lg:col-span-4 bg-violet-light/40 border border-violet-light rounded-xl px-5 py-4 flex items-center gap-3">
      <div class="w-9 h-9 rounded-full bg-white border border-violet-light grid place-items-center shrink-0">
        <span class="text-[16px] leading-none">{{ motivation.emoji }}</span>
      </div>
      <p class="text-[13.5px] text-ink2 leading-snug">
        <span class="font-semibold text-ink">{{ motivation.text }}</span>
      </p>
      <Sparkles :size="14" class="text-violet ml-auto shrink-0" />
    </div>
  </section>
</template>
