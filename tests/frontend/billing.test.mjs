import assert from "node:assert/strict";
import test from "node:test";
import { readFile } from "node:fs/promises";
import { canRetryInvoice, createBillingKey, outstandingAmount, settlementLabel } from "../../resources/js/data/billing.js";

test("development bypass is visible and removes provider payment wording", async () => {
  const pricing = await readFile(new URL("../../resources/js/views/Pricing.vue", import.meta.url), "utf8");
  assert.match(pricing, /developmentPaymentBypass/);
  assert.match(pricing, /Activate plan \(development\)/);
  assert.match(pricing, /requires_payment/);
});

test("billing idempotency keys are unique and bounded", () => {
  const first = createBillingKey("checkout"); const second = createBillingKey("checkout");
  assert.notEqual(first, second); assert.ok(first.startsWith("checkout-")); assert.ok(first.length <= 100);
});

test("invoice retry logic excludes terminal collection states", () => {
  assert.equal(outstandingAmount({ totalMinor: 2500, paidMinor: 1000 }), 1500);
  assert.equal(outstandingAmount({ totalMinor: 1000, paidMinor: 1500 }), 0);
  assert.equal(canRetryInvoice({ totalMinor: 2500, paidMinor: 0, status: "issued" }), true);
  assert.equal(canRetryInvoice({ totalMinor: 2500, paidMinor: 0, status: "uncollectible" }), false);
  assert.equal(canRetryInvoice({ totalMinor: 2500, paidMinor: 2500, status: "paid" }), false);
});

test("platform fees and institute earnings use separate user-facing names", () => {
  assert.equal(settlementLabel("platform_fee"), "Platform fee");
  assert.equal(settlementLabel("institute_earning"), "Institute earning");
});

test("billing views expose retry, document, refund and separated-ledger actions", async () => {
  const [student, admin, institute] = await Promise.all([
    readFile(new URL("../../resources/js/views/StudentBilling.vue", import.meta.url), "utf8"),
    readFile(new URL("../../resources/js/views/admin/AdminFinance.vue", import.meta.url), "utf8"),
    readFile(new URL("../../resources/js/views/institute/InstituteBilling.vue", import.meta.url), "utf8"),
  ]);
  assert.match(student, /downloadInvoice/); assert.match(student, /downloadReceipt/); assert.match(student, /canRetryInvoice/);
  assert.match(admin, /refundPayment/); assert.match(admin, /revoke_entitlement/);
  assert.match(institute, /Platform fee statement/); assert.match(institute, /Institute earnings statement/);
});
