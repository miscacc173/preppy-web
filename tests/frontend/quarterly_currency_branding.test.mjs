import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const finance = fs.readFileSync('resources/js/views/admin/AdminFinance.vue','utf8');
const planController = fs.readFileSync('app/Http/Controllers/Api/PlanController.php','utf8');
const subscriptionService = fs.readFileSync('app/Services/SubscriptionService.php','utf8');
const money = fs.readFileSync('resources/js/lib/money.ts','utf8');
const seed = fs.readFileSync('database/seeders/PreppyPlanSeeder.php','utf8');

test('plans support monthly quarterly and yearly billing with currency dropdown', () => {
  assert.match(finance, /<option value="month">Monthly<\/option>/);
  assert.match(finance, /<option value="quarter">Quarterly<\/option>/);
  assert.match(finance, /<option value="year">Yearly<\/option>/);
  assert.match(finance, /SUPPORTED_CURRENCIES/);
  assert.doesNotMatch(finance, /Currency<input v-model="form\.currency"/);
  assert.match(planController, /in:month,quarter,year/);
  assert.match(subscriptionService, /'quarter' => \$start->copy\(\)->addMonths\(3 \* \$plan->billing_interval_count\)/);
  assert.match(money, /Intl\.NumberFormat/);
  assert.match(seed, /PHYSICS_QUARTERLY/);
  assert.match(seed, /PHYSICS_YEARLY/);
});
