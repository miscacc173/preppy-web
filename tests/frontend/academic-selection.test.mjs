import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("signup, guest mode and learning-path setup share dependent catalog rules", async () => {
  const [auth, demo, student, selector] = await Promise.all([
    read("resources/js/views/auth/Auth.vue"), read("resources/js/views/Demo.vue"),
    read("resources/js/views/Student.vue"), read("resources/js/data/academicSelection.ts"),
  ]);
  assert.match(auth, /AcademicCatalogFields/);
  assert.match(demo, /AcademicCatalogFields/);
  assert.match(student, /boardsForCourse/);
  assert.match(student, /subjectsForCourse/);
  assert.match(selector, /boardsForCourse/);
  assert.match(selector, /subjectsForCourse/);
});

test("admin courses support multiple board and subject assignments", async () => {
  const admin = await read("resources/js/views/admin/AdminCatalog.vue");
  assert.match(admin, /form\.board_ids/);
  assert.match(admin, /Allowed exam boards/);
  assert.match(admin, /form\.subject_ids/);
});
