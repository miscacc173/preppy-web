import { createRouter, createWebHistory } from "vue-router";
import { api, currentUser, defaultAuthenticatedPath, fetchCurrentUser, token, type AuthUser } from "@/lib/api";
import { checkDashboardAccess } from "@/lib/subscriptionAccess";

// Phase 4: routes are added incrementally as each one is ported from
// src/routes/*.tsx (TanStack Start's file-based routing) — see
// CONVERSION_NOTES.md for progress. Nested `teacher.workspace.*` source files
// (TanStack's dot-notation for nested routes) become a parent route with
// vue-router's `children:` array once that section is ported.
const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("@/views/Landing.vue"),
  },
  {
    path: "/login",
    name: "login",
    component: () => import("@/views/auth/Login.vue"),
  },
  {
    path: "/signup",
    name: "signup",
    component: () => import("@/views/auth/Signup.vue"),
  },
  {
    path: "/auth/google/complete",
    name: "google-complete",
    component: () => import("@/views/auth/GoogleComplete.vue"),
  },
  {
    path: "/auth/apple/complete",
    name: "apple-complete",
    component: () => import("@/views/auth/AppleComplete.vue"),
  },
  {
    path: "/latest-news",
    name: "latest-news",
    component: () => import("@/views/LatestNews.vue"),
  },
  {
    path: "/support",
    name: "support",
    component: () => import("@/views/Support.vue"),
  },
  {
    path: "/help",
    name: "help",
    component: () => import("@/views/HelpCenter.vue"),
  },
  {
    path: "/pricing",
    name: "pricing",
    component: () => import("@/views/Pricing.vue"),
  },
  { path: "/billing", name: "student-billing", component: () => import("@/views/StudentBilling.vue"), meta: { roles: ["student"] } },
  {
    path: "/faq",
    name: "faq",
    component: () => import("@/views/Faq.vue"),
  },
  {
    path: "/demo",
    name: "demo",
    component: () => import("@/views/Demo.vue"),
  },
  {
    path: "/contact",
    name: "contact",
    component: () => import("@/views/Contact.vue"),
  },
  {
    path: "/features",
    name: "features",
    component: () => import("@/views/Features.vue"),
  },
  {
    path: "/onboarding",
    name: "onboarding",
    component: () => import("@/views/Onboarding.vue"),
    meta: { roles: ["student"] },
  },
  { path: "/parent/activate", name: "parent-activate", component: () => import("@/views/InviteActivation.vue"), props: { type: "parent" } },
  { path: "/institute/activate", name: "institute-activate", component: () => import("@/views/InviteActivation.vue"), props: { type: "institute" } },
  { path: "/educator/activate", name: "educator-activate", component: () => import("@/views/InviteActivation.vue"), props: { type: "educator" } },
  { path: "/parent", name: "parent-dashboard", component: () => import("@/views/ParentDashboard.vue"), meta: { roles: ["parent"] } },
  {
    path: "/language",
    name: "language",
    component: () => import("@/views/Language.vue"),
  },
  {
    path: "/dashboard",
    name: "dashboard",
    component: () => import("@/views/Dashboard.vue"),
    meta: { roles: ["student"], requiresSubscription: "dashboard" },
  },
  {
    path: "/learn",
    name: "learn",
    component: () => import("@/views/LearnLanding.vue"),
    meta: { roles: ["student", "teacher", "tutor"], requiresSubscription: "learning" },
  },
  {
    path: "/learn/session",
    name: "learn-session",
    component: () => import("@/views/LearnSession.vue"),
    meta: { roles: ["student", "teacher", "tutor"], requiresSubscription: "learning" },
  },
  {
    path: "/exam",
    name: "exam",
    component: () => import("@/views/ExamLanding.vue"),
    meta: { roles: ["student", "teacher", "tutor"], requiresSubscription: "learning" },
  },
  {
    path: "/exam/session",
    name: "exam-session",
    component: () => import("@/views/ExamSession.vue"),
    meta: { roles: ["student", "teacher", "tutor"], requiresSubscription: "learning" },
  },
  {
    path: "/results",
    name: "results",
    component: () => import("@/views/Results.vue"),
    meta: { roles: ["student", "teacher", "tutor"] },
  },
  {
    path: "/readiness",
    name: "readiness",
    component: () => import("@/views/Readiness.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/goals",
    name: "goals",
    component: () => import("@/views/Goals.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/calendar",
    name: "calendar",
    component: () => import("@/views/Calendar.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/settings",
    name: "settings",
    component: () => import("@/views/Settings.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/notifications",
    name: "notifications",
    component: () => import("@/views/Notifications.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/subjects",
    name: "subjects",
    component: () => import("@/views/Subjects.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/student",
    name: "student",
    component: () => import("@/views/Student.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/student-plan",
    name: "student-plan",
    component: () => import("@/views/StudentPlan.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/student-assessment",
    name: "student-assessment",
    component: () => import("@/views/StudentAssessment.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/student-insight",
    name: "student-insight",
    component: () => import("@/views/StudentInsight.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/study-preferences",
    name: "study-preferences",
    component: () => import("@/views/StudyPreferences.vue"),
    meta: { roles: ["student"] },
  },
  {
    path: "/teacher",
    component: () => import("@/views/teacher/TeacherLayout.vue"),
    meta: { roles: ["teacher"] },
    children: [
      { path: "", name: "teacher-onboarding", component: () => import("@/views/TeacherOnboarding.vue") },
      {
        path: "workspace",
        component: () => import("@/views/teacher/TeacherWorkspaceLayout.vue"),
        children: [
          { path: "", name: "teacher-workspace", component: () => import("@/views/teacher/WorkspaceOverview.vue") },
          { path: "assessments", component: () => import("@/views/teacher/WorkspaceAssessments.vue") },
          { path: "activities", component: () => import("@/views/teacher/WorkspaceActivities.vue") },
          { path: "insights", component: () => import("@/views/teacher/WorkspaceInsights.vue") },
          { path: "parents", component: () => import("@/views/teacher/WorkspaceParents.vue") },
          { path: "resources", component: () => import("@/views/teacher/WorkspaceResources.vue") },
          { path: "development", component: () => import("@/views/teacher/WorkspaceDevelopment.vue") },
          { path: "classes", component: () => import("@/views/teacher/WorkspaceClasses.vue") },
          { path: "practice/new", component: () => import("@/views/teacher/WorkspacePracticeNew.vue") },
          { path: "assessments/new", component: () => import("@/views/teacher/WorkspaceAssessmentsNew.vue") },
          { path: "activities/new", component: () => import("@/views/teacher/WorkspaceActivitiesNew.vue") },
          { path: "activities/:id", component: () => import("@/views/teacher/WorkspaceActivityDetail.vue") },
          { path: "assessments/:id", component: () => import("@/views/teacher/WorkspaceAssessmentDetail.vue") },
        ],
      },
    ],
  },
  { path: "/tutor/review", name: "tutor-review", component: () => import("@/views/tutor/TutorReview.vue"), meta: { roles: ["tutor"] } },
  {
    path: "/admin",
    component: () => import("@/views/admin/AdminLayout.vue"),
    meta: { roles: ["system_admin", "system_staff"] },
    children: [
      { path: "", name: "admin-overview", component: () => import("@/views/admin/AdminOverview.vue") },
      { path: "people", name: "admin-people", component: () => import("@/views/admin/AdminPeople.vue") },
      { path: "institutes", name: "admin-institutes", component: () => import("@/views/admin/AdminInstitutes.vue") },
      { path: "catalog", name: "admin-catalog", component: () => import("@/views/admin/AdminCatalog.vue") },
      { path: "finance", name: "admin-finance", component: () => import("@/views/admin/AdminFinance.vue") },
      { path: "access", name: "admin-access", component: () => import("@/views/admin/AdminAccess.vue"), meta: { roles: ["system_admin"] } },
      { path: "system", name: "admin-system", component: () => import("@/views/admin/AdminSystem.vue") },
      { path: "support", name: "admin-support", component: () => import("@/views/admin/AdminSupport.vue") },
      { path: "governance", name: "admin-governance", component: () => import("@/views/admin/AdminGovernance.vue") },
    ],
  },
  {
    path: "/institute/:instituteId(\\d+)",
    component: () => import("@/views/institute/InstituteLayout.vue"),
    meta: { roles: ["system_admin", "system_staff", "institute_admin", "institute_staff"] },
    children: [
      { path: "", name: "institute-overview", component: () => import("@/views/institute/InstituteOverview.vue") },
      { path: "students", name: "institute-students", component: () => import("@/views/institute/InstituteStudents.vue") },
      { path: "members", name: "institute-members", component: () => import("@/views/institute/InstituteMembers.vue"), meta: { roles: ["system_admin", "system_staff", "institute_admin"] } },
      { path: "classes", name: "institute-classes", component: () => import("@/views/institute/InstituteClasses.vue"), meta: { roles: ["system_admin", "system_staff", "institute_admin"] } },
      { path: "parents", name: "institute-parents", component: () => import("@/views/institute/InstituteParents.vue"), meta: { roles: ["system_admin", "institute_admin"] } },
      { path: "billing", name: "institute-billing", component: () => import("@/views/institute/InstituteBilling.vue"), meta: { roles: ["system_admin", "system_staff", "institute_admin"] } },
      { path: "settings", name: "institute-settings", component: () => import("@/views/institute/InstituteSettings.vue"), meta: { roles: ["system_admin", "system_staff", "institute_admin"] } },
    ],
  },
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: () => import("@/views/NotFound.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 };
  },
});

const PUBLIC_PATHS = new Set(["/", "/login", "/signup", "/auth/google/complete", "/auth/apple/complete", "/latest-news", "/pricing", "/faq", "/demo", "/contact", "/features", "/parent/activate", "/institute/activate", "/educator/activate"]);
let entitlementCache: { allowed: boolean; checkedAt: number; userId: number; feature: string } | null = null;

router.beforeEach(async (to) => {
  if (PUBLIC_PATHS.has(to.path)) {
    if ((to.path === "/login" || to.path === "/signup") && (token() || currentUser())) return defaultAuthenticatedPath(currentUser());
    return true;
  }
  let user: AuthUser | null = currentUser();
  if (!user || (token() && !user.roles?.length)) user = await fetchCurrentUser().catch(() => null);
  if (!user) return { path: "/login", query: { redirect: to.fullPath } };

  const required = [...to.matched].reverse().find((record) => record.meta.roles)?.meta.roles as string[] | undefined;
  if (!required?.length) return true;
  const roles = user.roles ?? [];
  if (!roles.some((role) => required.includes(role.slug))) return defaultAuthenticatedPath(user);

  const requiredSubscription = [...to.matched].reverse().find((record) => record.meta.requiresSubscription)?.meta.requiresSubscription as string | undefined;
  if (requiredSubscription && roles.some((role) => role.slug === "student")) {
    if (requiredSubscription === "dashboard") {
      const allowed = await checkDashboardAccess();
      if (!allowed) return { path: "/pricing", query: { reason: "plan_required" } };
      return true;
    }
    if (!entitlementCache || entitlementCache.userId !== user.id || entitlementCache.feature !== requiredSubscription || Date.now() - entitlementCache.checkedAt > 30_000) {
      const response = await api<{ data: { allowed: boolean } }>(`/billing/entitlements?feature=${encodeURIComponent(requiredSubscription)}`).catch(() => ({ data: { allowed: false } }));
      entitlementCache = { allowed: response.data.allowed, checkedAt: Date.now(), userId: user.id, feature: requiredSubscription };
    }
    if (!entitlementCache.allowed) return { path: "/billing", query: { reason: "subscription_required" } };
  }

  if (to.path.startsWith("/institute/") && !roles.some((role) => ["system_admin", "system_staff"].includes(role.slug))) {
    const requestedId = Number(to.params.instituteId);
    const allowed = roles.some((role) => ["institute_admin", "institute_staff"].includes(role.slug) && role.pivot?.context_id === requestedId);
    if (!allowed) return defaultAuthenticatedPath(user);
  }
  return true;
});

export default router;
