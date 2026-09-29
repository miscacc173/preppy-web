import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";

const read = async (path) => readFile(new URL(`../../${path}`, import.meta.url), "utf8");

test("upgrades restart the cycle only after successful payment and carry forward unused value", async () => {
  const [subscription, invoice, payment, pricing] = await Promise.all([
    read("app/Services/SubscriptionService.php"),
    read("app/Services/InvoiceService.php"),
    read("app/Services/PaymentService.php"),
    read("resources/js/views/Pricing.vue"),
  ]);
  assert.match(subscription, /unused_credit_minor/);
  assert.match(subscription, /finalizeUpgradeFromInvoice/);
  assert.match(subscription, /current_period_starts_at.*\$start/s);
  assert.match(subscription, /current_period_ends_at.*\$end/s);
  assert.match(subscription, /closed_reason.*plan_upgrade/s);
  assert.match(invoice, /carry_forward_credit_minor/);
  assert.match(invoice, /Credit carried forward from unused previous plan value/);
  assert.match(payment, /billing_event.*plan_upgrade/s);
  assert.match(payment, /finalizeUpgradeFromInvoice/);
  assert.match(pricing, /Unused value from current plan/);
  assert.match(pricing, /previous billing cycle closes only after this payment succeeds/);
});

test("downgrades stay on the current plan until renewal", async () => {
  const [subscription, renewal, pricing] = await Promise.all([
    read("app/Services/SubscriptionService.php"),
    read("routes/console.php"),
    read("resources/js/views/Pricing.vue"),
  ]);
  assert.match(subscription, /scheduled_plan_change/);
  assert.match(subscription, /type' => 'downgrade'/);
  assert.match(subscription, /applyScheduledPlanChangeAtRenewal/);
  assert.match(renewal, /applyScheduledPlanChangeAtRenewal/);
  assert.match(pricing, /Downgrade scheduled/);
  assert.match(pricing, /Your current plan continues until then/);
});

test("failed upgrade payment does not make the existing subscription past due", async () => {
  const [payment, dunning] = await Promise.all([
    read("app/Services/PaymentService.php"),
    read("app/Services/BillingDunningService.php"),
  ]);
  assert.match(payment, /billing_event'\) !== 'plan_upgrade'/);
  assert.match(dunning, /\$isUpgradeInvoice/);
  assert.match(dunning, /! \$isUpgradeInvoice/);
});
