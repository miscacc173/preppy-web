import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const source = (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("plan UI sends course and single or multiple subject targeting", async () => {
  const finance = await source("resources/js/views/admin/AdminFinance.vue");
  assert.match(finance, /course_id: form\.scope === "course"/);
  assert.match(finance, /subject_ids: \["subject", "multi_subject"\]/);
  assert.match(finance, /togglePlanSubject/);
  assert.match(finance, /Select two or more subjects/);
  assert.doesNotMatch(finance, /trial_days|Free trial/i);
});

test("public pricing and signup UI do not advertise or initiate trials", async () => {
  const [pricing, auth] = await Promise.all([
    source("resources/js/views/Pricing.vue"),
    source("resources/js/views/auth/Auth.vue"),
  ]);
  assert.doesNotMatch(pricing, /trialDays|free trial|trial activated/i);
  assert.doesNotMatch(auth, /free 7-day trial/i);
  assert.match(pricing, /Pay with/);
  assert.match(auth, /requestSignupEmailVerification/);
  assert.match(auth, /verifySignupEmail/);
  assert.match(auth, /Verify & create account/);
  assert.match(auth, /response\.data\.development_bypass && response\.data\.verification_grant/);
});

test("guest UI explains the one-session-per-email limit", async () => {
  const [demo, api] = await Promise.all([
    source("resources/js/views/Demo.vue"),
    source("resources/js/lib/platformApi.ts"),
  ]);
  assert.match(demo, /One guest diagnostic session is available per verified email/);
  assert.match(demo, /challenge\.development_bypass && challenge\.verification_grant/);
  assert.match(demo, /await startDiagnostic\(\)/);
  assert.match(api, /development_bypass: boolean/);
});

test("tutor can access both modes and receives separate inline feedback boxes", async () => {
  const [router, learn, exam, feedback] = await Promise.all([
    source("resources/js/router/index.ts"),
    source("resources/js/views/learn-session/SessionStage.vue"),
    source("resources/js/views/exam-session/ActiveStage.vue"),
    source("resources/js/components/app/TutorQuestionFeedback.vue"),
  ]);
  assert.match(router, /roles: \["student", "teacher", "tutor"\]/);
  assert.match(learn, /TutorQuestionFeedback/);
  assert.match(exam, /TutorQuestionFeedback/);
  assert.match(feedback, /`\$\{props\.questionKey\}:\$\{kind\}`/);
  assert.match(feedback, /question.*answer|answer.*question/s);
});
