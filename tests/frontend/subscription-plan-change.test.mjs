import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";

const read = async (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("student pricing can change an existing active plan instead of redirecting every alternative to management", async () => {
  const pricing = await read("resources/js/views/Pricing.vue");
  const repository = await read("resources/js/data/repositories/platformRepository.ts");
  const api = await read("resources/js/lib/platformApi.ts");
  const routes = await read("routes/api.php");
  const controller = await read("app/Http/Controllers/Api/SubscriptionController.php");

  assert.match(pricing, /canChange = !samePlan && \["active", "paused"\]\.includes\(status\)/);
  assert.match(pricing, /platformRepository\.changePlan\(activeSubscription\.value\.id, id\)/);
  assert.match(pricing, /Upgrade plan/);
  assert.match(repository, /async changePlan\(id: number, planId: number\)/);
  assert.match(api, /subscriptions\/\$\{id\}\/change-plan/);
  assert.match(routes, /subscriptions\/\{subscription\}\/change-plan/);
  assert.match(controller, /function changePlan\(/);
});

test("active-plan subject lookup falls back to plan and course definitions when stored entitlements are incomplete", async () => {
  const entitlement = await read("app/Services/SubscriptionEntitlementService.php");
  assert.match(entitlement, /if \(\$subjectIds->isEmpty\(\)\)/);
  assert.match(entitlement, /\$subscription->plan\?->subjects\(\)->pluck\('subjects\.id'\)/);
  assert.match(entitlement, /\$subscription->plan\?->course_id/);
  assert.match(entitlement, /\$user->studentProfile\?->course_id/);
});
