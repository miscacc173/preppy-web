import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";
import test from "node:test";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../..");
const read = (relative) => fs.readFileSync(path.join(root, relative), "utf8");

test("guest diagnostic conversion continues to matching plan activation", () => {
  const demo = read("resources/js/views/Demo.vue");
  const pricing = read("resources/js/views/Pricing.vue");
  const eligibility = read("resources/js/data/planEligibility.ts");
  const subscriptionService = read("app/Services/SubscriptionService.php");

  assert.match(demo, /path: "\/pricing", query: \{ from: "guest-diagnostic" \}/);
  assert.match(demo, /Create account & choose plan/);
  assert.match(pricing, /planMatchesAccount/);
  assert.match(pricing, /These plans match your selected course and subject/);
  assert.match(eligibility, /plan\.scope === "course"/);
  assert.match(eligibility, /\["subject", "multi_subject"\]/);
  assert.match(subscriptionService, /PlanEligibilityService::class/);
});
