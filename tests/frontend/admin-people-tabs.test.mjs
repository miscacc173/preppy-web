import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const source = await readFile(new URL("../../resources/js/views/admin/AdminPeople.vue", import.meta.url), "utf8");

test("admin people has a separate tab for every platform user role", () => {
  for (const [id, role] of [
    ["students", "student"],
    ["parents", "parent"],
    ["teachers", "teacher"],
    ["tutors", "tutor"],
    ["institute_admins", "institute_admin"],
    ["institute_staff", "institute_staff"],
    ["system_admins", "system_admin"],
    ["system_staff", "system_staff"],
  ]) {
    assert.match(source, new RegExp(`id: "${id}"[^\\n]+role: "${role}"`));
  }
});

test("role tabs filter assignments and preserve the student profile view", () => {
  assert.match(source, /item\.roles\.some\(\(assignment\) => assignment\.slug === role\)/);
  assert.match(source, /v-if="tab !== 'students'"/);
  assert.match(source, /title="Student profiles"/);
  assert.match(source, /ParentAccessModal/);
  assert.match(source, /EducatorAccessModal/);
});
