<script setup lang="ts">
import { computed } from "vue";
import { CreditCard, GraduationCap, LayoutDashboard, School, Settings, Users, UsersRound } from "lucide-vue-next";
import { useRoute } from "vue-router";
import OperationsShell from "@/components/operations/OperationsShell.vue";
import { currentUser } from "@/lib/api";

const route = useRoute();
const base = computed(() => `/institute/${route.params.instituteId}`);
const navigation = computed(() => {
  const roles = currentUser()?.roles ?? [];
  const canManage = roles.some((role) => ["system_admin", "system_staff", "institute_admin"].includes(role.slug));
  const canManageParents = roles.some((role) => ["system_admin", "institute_admin"].includes(role.slug));
  return [
    { label: "Overview", to: base.value, icon: LayoutDashboard },
    { label: "Students", to: `${base.value}/students`, icon: GraduationCap },
    ...(canManage ? [
      { label: "Members", to: `${base.value}/members`, icon: Users },
      { label: "Classes", to: `${base.value}/classes`, icon: School },
      ...(canManageParents ? [{ label: "Parent access", to: `${base.value}/parents`, icon: UsersRound }] : []),
      { label: "Billing", to: `${base.value}/billing`, icon: CreditCard },
      { label: "Institute settings", to: `${base.value}/settings`, icon: Settings },
    ] : []),
  ];
});
</script>

<template>
  <OperationsShell eyebrow="Institute workspace" title="Organisation management" :navigation="navigation" back-to="/">
    <RouterView />
  </OperationsShell>
</template>
