<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Building2, GraduationCap, RefreshCw, Users, WalletCards } from "lucide-vue-next";
import DataPanel from "@/components/operations/DataPanel.vue";
import MetricCard from "@/components/operations/MetricCard.vue";
import StatusBadge from "@/components/operations/StatusBadge.vue";
import { operationsRepository } from "@/data/repositories/operationsRepository";
import type { UiAuditRecord, UiUserRecord } from "@/data/models/operations";

const loading = ref(true);
const error = ref("");
const counts = ref({ users: 0, students: 0, institutes: 0, subscriptions: 0 });
const recentUsers = ref<UiUserRecord[]>([]);
const recentAudits = ref<UiAuditRecord[]>([]);

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const [users, students, institutes, subscriptions, audits] = await Promise.all([
      operationsRepository.users({ per_page: 5 }),
      operationsRepository.students({ per_page: 1 }),
      operationsRepository.institutes({ per_page: 1 }),
      operationsRepository.subscriptions({ per_page: 1 }),
      operationsRepository.audits({ per_page: 6 }),
    ]);
    counts.value = { users: users.total, students: students.total, institutes: institutes.total, subscriptions: subscriptions.total };
    recentUsers.value = users.items;
    recentAudits.value = audits.items;
  } catch (reason) { error.value = reason instanceof Error ? reason.message : "Unable to load administration data."; }
  finally { loading.value = false; }
}

onMounted(load);
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-[12px] font-semibold uppercase tracking-[0.12em] text-blue">Live platform data</p>
        <h2 class="mt-1 text-[26px] font-bold tracking-[-0.035em] text-ink">Operations overview</h2>
        <p class="mt-1 text-[13px] text-ink3">Users, learning accounts and commercial activity from Laravel APIs.</p>
      </div>
      <button class="flex items-center gap-2 rounded-xl border border-border bg-bg-card px-3.5 py-2 text-[12px] font-semibold text-ink2 hover:bg-bg-soft" @click="load">
        <RefreshCw :size="14" :class="loading && 'animate-spin'" /> Refresh
      </button>
    </div>

    <p v-if="error" class="rounded-xl bg-pink-light px-4 py-3 text-[13px] text-pink">{{ error }}</p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <MetricCard label="Users" :value="loading ? '—' : counts.users" :icon="Users" hint="Registered platform accounts" />
      <MetricCard label="Students" :value="loading ? '—' : counts.students" :icon="GraduationCap" tone="violet" hint="Student profiles" />
      <MetricCard label="Institutes" :value="loading ? '—' : counts.institutes" :icon="Building2" tone="mint" hint="Managed organisations" />
      <MetricCard label="Subscriptions" :value="loading ? '—' : counts.subscriptions" :icon="WalletCards" tone="gold" hint="All subscription records" />
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.05fr_.95fr]">
      <DataPanel title="Newest users" description="Recently created user accounts" :loading="loading" :empty="!recentUsers.length">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-[12px]">
            <thead class="bg-bg-soft text-ink3"><tr><th class="px-5 py-3 font-semibold">User</th><th class="px-4 py-3 font-semibold">Role</th><th class="px-5 py-3 font-semibold">Status</th></tr></thead>
            <tbody class="divide-y divide-border">
              <tr v-for="user in recentUsers" :key="user.id">
                <td class="px-5 py-3.5"><p class="font-semibold text-ink">{{ user.name }}</p><p class="mt-0.5 text-ink3">{{ user.email }}</p></td>
                <td class="px-4 py-3.5 text-ink2">{{ user.roles.map((role) => role.slug.replaceAll('_', ' ')).join(', ') || 'No role' }}</td>
                <td class="px-5 py-3.5"><StatusBadge :value="user.status" /></td>
              </tr>
            </tbody>
          </table>
        </div>
      </DataPanel>

      <DataPanel title="Audit activity" description="Latest recorded administrative events" :loading="loading" :empty="!recentAudits.length">
        <div class="divide-y divide-border">
          <div v-for="audit in recentAudits" :key="audit.id" class="flex items-start gap-3 px-5 py-3.5">
            <div class="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-blue" />
            <div class="min-w-0 flex-1"><p class="truncate text-[12px] font-semibold text-ink">{{ audit.action.replaceAll('_', ' ') }}</p><p class="mt-0.5 text-[11px] text-ink3">{{ audit.target || 'System event' }} · actor {{ audit.actorId || 'system' }}</p></div>
            <span class="text-[10px] text-ink4">{{ audit.createdAt ? new Date(audit.createdAt).toLocaleDateString() : '' }}</span>
          </div>
        </div>
      </DataPanel>
    </div>
  </div>
</template>
