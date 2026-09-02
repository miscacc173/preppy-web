import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";
import test from "node:test";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../..");
const read = (relative) => fs.readFileSync(path.join(root, relative), "utf8");

test("topic seeder covers every seeded subject and is wired after academic data", () => {
  const seeder = read("database/seeders/TopicSeeder.php");
  const testingData = read("database/seeders/TestingDataSeeder.php");

  for (const code of ["5054", "5070", "5090", "4024", "2210", "1123"]) {
    assert.match(seeder, new RegExp(`'${code}'\\s*=>`));
  }
  assert.match(seeder, /updateOrInsert/);
  assert.match(seeder, /\['subject_id' => \$subject->id, 'code' => \$code\]/);
  assert.ok(testingData.indexOf("TestingAcademicDataSeeder::class") < testingData.indexOf("TopicSeeder::class"));
});
