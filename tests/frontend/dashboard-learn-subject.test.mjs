import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";
import test from "node:test";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../..");
const read = (relative) => fs.readFileSync(path.join(root, relative), "utf8");

test("dashboard subject navigation preselects the allowed Learn Mode subject", () => {
  const dashboard = read("resources/js/views/Dashboard.vue");
  const learn = read("resources/js/views/LearnSession.vue");

  assert.match(dashboard, /path: '\/learn\/session'/);
  assert.match(dashboard, /subject: subject\.name/);
  assert.match(learn, /route\.query\.subject/);
  assert.match(learn, /subscription\.value\.subjects\.find/);
  assert.match(learn, /setWiz\(\{ subject: requested, topics: \[\] \}\)/);
  assert.match(learn, /route\.query\.resume \|\| route\.query\.sid/);
});
