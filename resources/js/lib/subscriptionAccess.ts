import { readonly, ref } from "vue";
import { api, currentUser } from "@/lib/api";

type DashboardAccessState = "unknown" | "checking" | "allowed" | "denied";

const dashboardAccessState = ref<DashboardAccessState>("unknown");
let checkedAt = 0;
let checkedUserId: number | null = null;
let pending: Promise<boolean> | null = null;

function isStudent() {
  return currentUser()?.roles?.some((role) => role.slug === "student") ?? false;
}

export async function checkDashboardAccess(force = false): Promise<boolean> {
  if (!isStudent()) {
    dashboardAccessState.value = "allowed";
    return true;
  }
  const userId = currentUser()?.id ?? null;
  if (!force && checkedUserId === userId && dashboardAccessState.value !== "unknown" && Date.now() - checkedAt < 30_000) {
    return dashboardAccessState.value === "allowed";
  }
  if (pending && !force) return pending;

  dashboardAccessState.value = "checking";
  pending = api<{ data: { allowed: boolean } }>("/billing/entitlements?feature=dashboard")
    .then((response) => {
      dashboardAccessState.value = response.data.allowed ? "allowed" : "denied";
      checkedUserId = userId;
      checkedAt = Date.now();
      return response.data.allowed;
    })
    .catch(() => {
      dashboardAccessState.value = "denied";
      checkedUserId = userId;
      checkedAt = Date.now();
      return false;
    })
    .finally(() => { pending = null; });
  return pending;
}

export function resetDashboardAccess() {
  dashboardAccessState.value = "unknown";
  checkedAt = 0;
  checkedUserId = null;
  pending = null;
}

export const dashboardAccess = readonly(dashboardAccessState);
