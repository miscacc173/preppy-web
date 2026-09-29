import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const source = (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("logout clears server and browser identity in one flow", async () => {
  const api = await source("resources/js/lib/api.ts");
  assert.match(api, /Always call logout/);
  assert.match(api, /await api\("\/auth\/logout"/);
  assert.match(api, /clearLocalUserState/);
  assert.match(api, /clearAuth\(true\)/);
});

test("Google sign-in exchanges the fresh web session and new students enter onboarding", async () => {
  const [google, social, complete, router, main] = await Promise.all([
    source("app/Http/Controllers/Auth/GoogleAuthController.php"),
    source("app/Services/Auth/SocialLoginService.php"),
    source("resources/js/views/auth/GoogleComplete.vue"),
    source("resources/js/router/index.ts"),
    source("resources/js/main.ts"),
  ]);
  assert.match(google, /SocialLoginService/);
  assert.match(social, /withTrashed\(\)/);
  assert.match(social, /incompleteAcademicSetup/);
  assert.match(google, /auth\/google\/complete\?onboarding=/);
  assert.match(complete, /exchangeWebSession/);
  assert.match(complete, /\/onboarding\?source=google/);
  assert.match(router, /\/auth\/google\/complete/);
  assert.match(main, /socialLoginCompleting/);
});

test("admin user removal deactivates instead of deleting identity", async () => {
  const [controller, people] = await Promise.all([
    source("app/Http/Controllers/Api/UserController.php"),
    source("resources/js/views/admin/AdminPeople.vue"),
  ]);
  assert.match(controller, /status' => 'disabled'/);
  assert.doesNotMatch(controller, /->delete\(\)/);
  assert.match(people, /Deactivate user/);
});

test("admin avatar exposes only light and dark appearance choices", async () => {
  const [shell, admin] = await Promise.all([
    source("resources/js/components/operations/OperationsShell.vue"),
    source("resources/js/views/admin/AdminLayout.vue"),
  ]);
  assert.match(admin, /theme-menu/);
  assert.match(shell, /Light mode/);
  assert.match(shell, /Dark mode/);
  assert.match(shell, /setTheme\('light'\)/);
  assert.match(shell, /setTheme\('dark'\)/);
});

test("plan creation is course-first and server validates each course subject mapping", async () => {
  const [controller, migration, eligibility] = await Promise.all([
    source("app/Http/Controllers/Api/PlanController.php"),
    source("database/migrations/2026_09_03_050000_create_plan_course_subjects.php"),
    source("app/Services/PlanEligibilityService.php"),
  ]);
  assert.match(controller, /course_subjects.*required\|array\|min:1/s);
  assert.match(controller, /Each selected course must contain at least one subject/);
  assert.match(controller, /course_subjects/);
  assert.match(migration, /plan_course_subjects/);
  assert.match(eligibility, /courseSubjectSelections/);
});

test("multi-course subscriptions only grant subjects for the student's selected course", async () => {
  const service = await source("app/Services/SubscriptionService.php");
  assert.match(service, /courseSubjectSelections->where\('course_id', \$courseId\)/);
  assert.match(service, /subject_ids' => \$subjectIds/);
});
