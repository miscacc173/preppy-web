import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const view = await readFile(new URL("../../resources/js/views/ParentDashboard.vue", import.meta.url), "utf8");
const mapper = await readFile(new URL("../../resources/js/data/mappers/parentMapper.ts", import.meta.url), "utf8");
const service = await readFile(new URL("../../app/Services/StudentProgressService.php", import.meta.url), "utf8");
const entitlementService = await readFile(new URL("../../app/Services/SubscriptionEntitlementService.php", import.meta.url), "utf8");
const controller = await readFile(new URL("../../app/Http/Controllers/Api/ParentStudentController.php", import.meta.url), "utf8");

test("parent portal presents detailed progress for every subject", () => {
  assert.match(view, /Subject performance/);
  assert.match(view, /subjectProgress\(link\)/);
  assert.match(view, /Recent daily activity/);
  for (const heading of ["Date", "Mode", "Topic / paper", "Score", "Grade", "Marks", "Questions", "Duration", "Status"]) {
    assert.match(view, new RegExp(`>${heading}<`));
  }
});

test("parent subject cards are restricted to the active-plan subject list", () => {
  assert.match(view, /const subjects = \[\.\.\.new Set\(link\.student\.subjects\)\]/);
  assert.doesNotMatch(view, /\.\.\.details\.keys\(\)/);
  assert.match(view, /active-plan subject\(s\)/);
  assert.match(view, /No subjects are available in this student's active plan/);
  assert.match(entitlementService, /activePlanSubjectsFor/);
  assert.match(entitlementService, /\['subject', 'multi_subject'\]/);
  assert.match(entitlementService, /if \(\$scope === 'course'\)/);
  assert.match(controller, /summary\(\$link->student_user_id, \$planSubjectNames->all\(\)\)/);
  assert.match(service, /\?array \$allowedSubjects = null/);
});

test("each linked-student card shows the student's identity once in its header", () => {
  assert.match(view, /v-for="\(link, studentIndex\) in links"/);
  assert.match(view, /Student \{\{ studentIndex \+ 1 \}\} of \{\{ links\.length \}\}/);
  assert.match(view, /Student: \{\{ link\.student\.name \}\}/);
  assert.equal(view.match(/\{\{ link\.student\.name \}\}/g)?.length, 1);
  assert.match(view, />Subject performance</);
  assert.match(view, />Recent daily activity</);
});

test("parent progress API and mapper retain persisted session detail", () => {
  for (const field of ["earned", "total_marks", "grade", "question_count", "correct_count", "started_at", "completed_at"]) {
    assert.match(service, new RegExp(`'${field}'`));
  }
  for (const field of ["recent_sessions", "average_score", "best_score", "latest_score", "last_session_at"]) {
    assert.match(mapper, new RegExp(`"${field}"`));
  }
});
