<script setup lang="ts">
import { computed } from "vue";
import { Activity, Building2, CreditCard, GraduationCap, LayoutDashboard, LibraryBig, MessageSquareText, MonitorSmartphone, ShieldCheck } from "lucide-vue-next";
import OperationsShell from "@/components/operations/OperationsShell.vue";
import { currentUser } from "@/lib/api";

const navigation = computed(() => [
  { label: "Overview", to: "/admin", icon: LayoutDashboard },
  { label: "Users & students", to: "/admin/people", icon: GraduationCap },
  { label: "Institutes", to: "/admin/institutes", icon: Building2 },
  { label: "Academic catalog", to: "/admin/catalog", icon: LibraryBig },
  { label: "Billing & plans", to: "/admin/finance", icon: CreditCard },
  ...(currentUser()?.roles?.some((role) => role.slug === "system_admin") ? [{ label: "Access control", to: "/admin/access", icon: ShieldCheck }] : []),
  { label: "System data", to: "/admin/system", icon: MonitorSmartphone },
  { label: "Support inbox", to: "/admin/support", icon: MessageSquareText },
  { label: "Governance", to: "/admin/governance", icon: Activity },
]);
</script>

<template>
  <OperationsShell eyebrow="System operations" title="Platform administration" :navigation="navigation" back-to="/dashboard" theme-menu>
    <RouterView />
  </OperationsShell>
</template>
