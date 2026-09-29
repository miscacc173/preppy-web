import assert from "node:assert/strict";
import { readFileSync } from "node:fs";

const finance = readFileSync(new URL("../../resources/js/views/admin/AdminFinance.vue", import.meta.url), "utf8");
const access = readFileSync(new URL("../../resources/js/views/admin/AdminAccess.vue", import.meta.url), "utf8");
const promos = readFileSync(new URL("../../resources/js/views/admin/AdminPromotionsReferrals.vue", import.meta.url), "utf8");

assert.match(finance, /courses\.value = coursePage\.items\.filter\(/, "plan form must filter courses");
assert.match(finance, /course\.subjectIds\.some/, "course visibility must require at least one actual subject");
assert.doesNotMatch(access, />Permissions<\/button>/, "admin permission directory tab must be hidden");
assert.match(access, /Role directory/, "roles remain manageable");
assert.match(promos, /Create promotion/, "admin must have create-promotion action");
assert.match(promos, /createInstitutePromoCode/, "admin create action must use institute-owned promotion flow");
assert.match(promos, /Referrals are created automatically/, "referrals must remain event-driven");
assert.match(promos, /Redemptions are financial audit records and are never created manually/, "redemptions must remain payment-driven");
console.log("admin UI gap regression checks passed");
