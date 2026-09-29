import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";

const read = (p) => fs.readFileSync(new URL(`../../${p}`, import.meta.url), "utf8");

test("notification policy separates preferences, marketing opt-in, and mandatory categories", () => {
  const policy = read("app/Services/Notifications/NotificationPolicy.php");
  assert.match(policy, /marketing_opt_in/);
  assert.match(policy, /marketing_promotions/);
  assert.match(policy, /'billing'.*'account'.*'security'.*'subscription'/s);
  assert.match(policy, /'mandatory'/);
});

test("settings exposes promotional opt-in and Preppy branding", () => {
  const settings = read("resources/js/views/Settings.vue");
  assert.match(settings, /Offers & promotions/);
  assert.match(settings, /marketing_promotions/);
  assert.doesNotMatch(settings, /AiStar Prep/);
});

test("admin can send promotion, announcement, and system notifications", () => {
  const view = read("resources/js/views/admin/AdminSystem.vue");
  const ctrl = read("app/Http/Controllers/Api/PushNotificationController.php");
  assert.match(view, /Promotion — opt-in only/);
  assert.match(view, /Announcement — mandatory/);
  assert.match(view, /System — mandatory/);
  assert.match(ctrl, /admin_campaign/);
  assert.match(ctrl, /audience.*all,role,user/s);
});
