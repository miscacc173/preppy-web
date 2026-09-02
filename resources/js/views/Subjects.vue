<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { setPageMeta } from "@/lib/useRouteHead";
import AppShell from "@/components/app/AppShell.vue";
import { BookOpen, GraduationCap, Award, Calendar, BarChart3, Plus, Trash2, ArrowUpRight } from "lucide-vue-next";
import { platformRepository } from "@/data/repositories/platformRepository";
import type { UiAccount, UiSubscription } from "@/data/models/platform";
import { mapAcademicSetup, mapSubjectCard, mapSubscriptionInfo } from "@/data/mappers/subjectViewMapper";

setPageMeta({ title: "Subjects & Exam Boards — AiStar Prep" });

const account = ref<UiAccount | null>(null);
const subscription = ref<UiSubscription | null>(null);
const primary = ref("");
const loading = ref(true);
const error = ref("");

const subjectsList = computed(() => (account.value?.subjects ?? []).map(mapSubjectCard));

const academicSetup = computed(() => account.value ? mapAcademicSetup(account.value) : []);

const subscriptionInfo = computed(() => mapSubscriptionInfo(subscription.value, subjectsList.value.length));

onMounted(async () => {
  try {
    const [accountResponse, subscriptions] = await Promise.all([platformRepository.account(), platformRepository.subscriptions()]);
    account.value = accountResponse.account;
    subscription.value = subscriptions[0] ?? null;
    primary.value = subjectsList.value[0]?.name ?? "";
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : "Your subjects could not be loaded.";
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <AppShell title="Subjects & Exam Boards" :back="{ to: '/dashboard', label: 'Back to Dashboard' }">
    <div class="space-y-5 max-w-[1100px]">
      <div v-if="loading" class="bg-bg-card border border-border rounded-xl p-5 text-[13px] text-ink3">Loading subjects…</div>
      <div v-if="error" class="bg-pink-light border border-pink/30 rounded-xl p-4 text-[13px] text-pink">{{ error }}</div>
      <!-- Academic Setup -->
      <section class="bg-bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-9 h-9 rounded-lg grid place-items-center bg-violet-light text-violet"><GraduationCap :size="16" /></span>
          <h3 class="text-[16px] font-semibold text-ink">Current Academic Setup</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div v-for="x in academicSetup" :key="x.l" class="p-3 rounded-lg bg-bg-soft border border-border">
            <p class="text-[11px] text-ink3 uppercase tracking-wider">{{ x.l }}</p>
            <p class="text-[14px] font-semibold text-ink mt-1">{{ x.v }}</p>
          </div>
        </div>
      </section>

      <!-- Subscribed Subjects -->
      <section class="bg-bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg grid place-items-center bg-blue-light text-blue"><BookOpen :size="16" /></span>
            <h3 class="text-[16px] font-semibold text-ink">My Subscribed Subjects</h3>
          </div>
          <p class="text-[12px] text-ink3">Tap to set primary</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <button
            v-for="s in subjectsList"
            :key="s.name"
            :class="`text-left p-4 rounded-xl border transition ${primary === s.name ? 'bg-blue-light/50 border-blue' : 'bg-bg-soft border-border hover:bg-bg-soft2'}`"
            @click="primary = s.name"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="text-[14px] font-semibold text-ink">{{ s.name }}</span>
              <span v-if="primary === s.name" class="text-[10px] font-semibold text-blue bg-blue-light border border-blue/30 rounded-full px-2 py-0.5">Primary</span>
            </div>
            <div class="flex items-center gap-4 text-[11.5px] text-ink3">
              <span>Progress <b class="text-ink">{{ s.progress }}%</b></span>
              <span>Predicted <b class="text-ink">{{ s.predicted }}</b></span>
              <span>Readiness <b class="text-ink">{{ s.readiness }}</b></span>
            </div>
          </button>
          <p v-if="!loading && subjectsList.length === 0" class="col-span-full text-[13px] text-ink3 py-3">No subjects selected yet. Update your academic setup to add subjects.</p>
        </div>
      </section>

      <!-- Subject Overview -->
      <section class="bg-bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-9 h-9 rounded-lg grid place-items-center bg-emerald-light text-emerald"><BarChart3 :size="16" /></span>
          <h3 class="text-[16px] font-semibold text-ink">Subject Overview</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-[12.5px]">
            <thead>
              <tr class="text-ink3 text-left border-b border-border">
                <th class="py-2 font-medium">Subject</th>
                <th class="font-medium">Readiness</th>
                <th class="font-medium">Attempted</th>
                <th class="font-medium">Avg Marks</th>
                <th class="font-medium">Predicted</th>
                <th class="font-medium">Last Activity</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in subjectsList" :key="s.name" class="border-b border-border last:border-0">
                <td class="py-2.5 font-medium text-ink">{{ s.name }}</td>
                <td class="text-ink2">{{ s.readiness }}</td>
                <td class="text-ink2">{{ s.attempted }}</td>
                <td class="text-ink2">{{ s.avg }}%</td>
                <td><span class="text-[11px] font-semibold text-violet bg-violet-light rounded-full px-2 py-0.5">{{ s.predicted }}</span></td>
                <td class="text-ink3">{{ s.last }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Exam Board Info -->
      <section class="bg-bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-9 h-9 rounded-lg grid place-items-center bg-gold-light text-gold"><Award :size="16" /></span>
          <h3 class="text-[16px] font-semibold text-ink">Exam Board Information</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="x in [
              { l: 'Exam Board', v: account?.examBoard?.name ?? 'Not selected' },
              { l: 'Board Code', v: account?.examBoard?.code ?? '—' },
              { l: 'Course', v: account?.course?.name ?? 'Not selected' },
              { l: 'School Year', v: account?.schoolYear ?? 'Not selected' },
            ]"
            :key="x.l"
            class="p-3 rounded-lg bg-bg-soft border border-border"
          >
            <p class="text-[11px] text-ink3 uppercase tracking-wider">{{ x.l }}</p>
            <p class="text-[13.5px] font-semibold text-ink mt-1">{{ x.v }}</p>
          </div>
        </div>
      </section>

      <!-- Learning Coverage -->
      <section class="bg-bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-9 h-9 rounded-lg grid place-items-center bg-blue-light text-blue"><BarChart3 :size="16" /></span>
          <h3 class="text-[16px] font-semibold text-ink">Learning Coverage</h3>
        </div>
        <div class="space-y-3">
          <div v-for="s in subjectsList" :key="s.name">
            <div class="flex items-center justify-between text-[12.5px] mb-1">
              <span class="font-medium text-ink2">{{ s.name }}</span>
              <span class="text-ink3">{{ s.progress }}%</span>
            </div>
            <div class="h-2 rounded-full bg-bg-soft overflow-hidden">
              <div class="h-full bg-blue rounded-full" :style="{ width: `${s.progress}%` }" />
            </div>
          </div>
        </div>
      </section>

      <!-- Subscription -->
      <section class="bg-bg-card border border-border rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg grid place-items-center bg-violet-light text-violet"><Calendar :size="16" /></span>
            <h3 class="text-[16px] font-semibold text-ink">Subscription Information</h3>
          </div>
          <RouterLink to="/pricing" class="text-[12px] font-semibold text-blue hover:underline inline-flex items-center gap-1">
            Upgrade <ArrowUpRight :size="12" />
          </RouterLink>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <div
            v-for="x in subscriptionInfo"
            :key="x.l"
            class="p-3 rounded-lg bg-bg-soft border border-border"
          >
            <p class="text-[11px] text-ink3 uppercase tracking-wider">{{ x.l }}</p>
            <p class="text-[13.5px] font-semibold text-ink mt-1">{{ x.v }}</p>
          </div>
        </div>
      </section>

      <!-- Management -->
      <section class="bg-bg-card border border-border rounded-2xl p-6">
        <h3 class="text-[16px] font-semibold text-ink mb-1">Subject Management</h3>
        <p class="text-[12.5px] text-ink3 mb-4">Add or remove subjects through your academic setup.</p>
        <div class="flex flex-wrap gap-2">
          <RouterLink to="/student" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-bg-soft border border-border text-[12.5px] text-ink2 hover:bg-bg-soft2">
            <Plus :size="13" /> Add Subject
          </RouterLink>
          <RouterLink to="/student" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-bg-soft border border-border text-[12.5px] text-ink2 hover:bg-bg-soft2">
            <Trash2 :size="13" /> Remove Subject
          </RouterLink>
          <RouterLink to="/pricing" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue text-white text-[12.5px] font-semibold hover:bg-blue-mid transition">
            <ArrowUpRight :size="13" /> Upgrade Subscription
          </RouterLink>
        </div>
      </section>
    </div>
  </AppShell>
</template>
