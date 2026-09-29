import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const router = await readFile(new URL("../../resources/js/router/index.ts", import.meta.url), "utf8");
const access = await readFile(new URL("../../resources/js/lib/subscriptionAccess.ts", import.meta.url), "utf8");
const topNav = await readFile(new URL("../../resources/js/components/site/TopNav.vue", import.meta.url), "utf8");
const appShell = await readFile(new URL("../../resources/js/components/app/AppShell.vue", import.meta.url), "utf8");
const pricing = await readFile(new URL("../../resources/js/views/Pricing.vue", import.meta.url), "utf8");
const backend = await readFile(new URL("../../app/Services/SubscriptionEntitlementService.php", import.meta.url), "utf8");

test("student dashboard requires a backend-confirmed active plan", () => {
  assert.match(router, /requiresSubscription: "dashboard"/);
  assert.match(router, /checkDashboardAccess\(\)/);
  assert.match(router, /path: "\/pricing", query: \{ reason: "plan_required" \}/);
  assert.match(access, /billing\/entitlements\?feature=dashboard/);
  assert.match(backend, /status' => 'active'/);
  assert.match(backend, /current_period_ends_at->isFuture\(\)/);
});

test("dashboard navigation stays hidden until activation succeeds", () => {
  assert.match(topNav, /canOpenDashboard\.value \? "Dashboard" : "Activate plan"/);
  assert.match(appShell, /item\.to !== "\/dashboard" \|\| canOpenDashboard\.value/);
  assert.match(pricing, /await checkDashboardAccess\(true\)/);
  assert.match(pricing, /The plan has not reached active status yet/);
});
