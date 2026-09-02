import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";

const finance = fs.readFileSync("resources/js/views/admin/AdminFinance.vue", "utf8");
const view = fs.readFileSync("resources/js/views/admin/AdminPromotionsReferrals.vue", "utf8");
const repo = fs.readFileSync("resources/js/data/repositories/operationsRepository.ts", "utf8");
const routes = fs.readFileSync("routes/api.php", "utf8");
const controller = fs.readFileSync("app/Http/Controllers/Api/AdminPromotionReferralController.php", "utf8");

test("admin finance exposes platform-wide promotions and referral oversight", () => {
  assert.match(finance, /Promotions & referrals/);
  assert.match(finance, /AdminPromotionsReferrals/);
  assert.match(view, /Institute promo codes/);
  assert.match(view, /Referrals & rewards/);
  assert.match(view, /Promotion redemptions/);
  assert.match(view, /Export CSV/);
  assert.match(view, /All institutes/);
  assert.match(view, /Name or email/);
});

test("admin promotion reporting and promo status control are admin-only backend actions", () => {
  assert.match(routes, /admin\/promotions-referrals/);
  assert.match(routes, /admin\/institute-promo-codes\/\{promoCode\}\/status/);
  assert.match(repo, /adminPromotionsReferrals/);
  assert.match(repo, /updateAdminInstitutePromoStatus/);
  assert.match(repo, /exportAdminPromotionsReferrals/);
  assert.match(controller, /hasSystemRole\('system_admin'\)/);
  assert.match(controller, /successful_redemptions/);
  assert.match(controller, /referral_rewards_issued/);
  assert.match(controller, /promotion_redemptions/);
});
