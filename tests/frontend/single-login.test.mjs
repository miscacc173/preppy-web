import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";
import test from "node:test";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../..");
const read = (relative) => fs.readFileSync(path.join(root, relative), "utf8");

test("all API token issuers enforce one active login through the shared service", () => {
  const service = read("app/Services/SingleLoginSessionService.php");
  assert.match(service, /tokens\(\)->delete\(\)/);
  assert.match(service, /where\('user_id', \$user->id\)/);
  assert.match(service, /lockForUpdate\(\)/);

  const controllerDirectory = path.join(root, "app/Http/Controllers");
  const files = [];
  const visit = (directory) => {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
      const absolute = path.join(directory, entry.name);
      if (entry.isDirectory()) visit(absolute);
      else if (entry.name.endsWith(".php")) files.push(absolute);
    }
  };
  visit(controllerDirectory);

  const directIssuers = files.filter((file) => /createToken\(/.test(fs.readFileSync(file, "utf8")));
  assert.deepEqual(directIssuers, [], "Controllers must not bypass SingleLoginSessionService");

  for (const relative of [
    "app/Http/Controllers/Api/AuthController.php",
    "app/Http/Controllers/Api/GuestDemoController.php",
    "app/Http/Controllers/Api/ParentStudentController.php",
    "app/Http/Controllers/Api/InstituteMemberController.php",
    "app/Http/Controllers/Api/EducatorInviteController.php",
  ]) {
    assert.match(read(relative), /SingleLoginSessionService/);
  }
});
