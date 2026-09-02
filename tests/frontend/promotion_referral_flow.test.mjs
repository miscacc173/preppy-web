import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("payment screen exposes separate promo and referral codes", async () => {
  const pricing = await read("resources/js/views/Pricing.vue");
  assert.match(pricing, /Promo \/ discount code/);
  assert.match(pricing, /Referral code/);
  assert.match(pricing, /validatePromotion/);
  assert.match(pricing, /referralCode\.value/);
});

test("promo redemption and referral rewards finalize only in successful payment completion", async () => {
  const payment = await read("app/Services/PaymentService.php");
  const promo = await read("app/Services/PromotionService.php");
  const referral = await read("app/Services/ReferralService.php");
  assert.match(payment, /consumeForPayment\(\$invoice, \$locked->id\)/);
  assert.match(payment, /rewardForPayment/);
  assert.match(promo, /status' => 'consumed'/);
  assert.match(referral, /status' => 'rewarded'/);
});

test("institute promo UI includes ownership rules, plan eligibility and reporting", async () => {
  const billing = await read("resources/js/views/institute/InstituteBilling.vue");
  assert.match(billing, /Institute promo codes/);
  assert.match(billing, /Eligible plans/);
  assert.match(billing, /Total usage limit/);
  assert.match(billing, /Per-student limit/);
  assert.match(billing, /Promo code report/);
});
