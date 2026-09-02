<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingPlanBillingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $courseId = DB::table('courses')->where('code', 'CAIE_O_LEVEL_2026')->value('id');
        $physicsId = DB::table('subjects')->where('code', '5054')->where('syllabus_year_start', 2026)->value('id');
        $mathId = DB::table('subjects')->where('code', '4024')->where('syllabus_year_start', 2026)->value('id');
        $instituteId = DB::table('institutes')->where('code', 'AISTAR-DEMO-001')->value('id');
        $adminId = DB::table('users')->where('email', 'admin@aistar.test')->value('id');

        $plans = [
            ['Physics Monthly', 'PHYSICS_MONTHLY', 'subject', null, 1499, 0, [$physicsId]],
            ['Science Duo', 'SCIENCE_DUO', 'multi_subject', null, 2499, 0, [$physicsId, $mathId]],
            ['O Level Complete', 'OLEVEL_COMPLETE', 'course', $courseId, 4999, 0, []],
            ['All Subjects Premium', 'ALL_SUBJECTS_PREMIUM', 'all_subjects', null, 6999, 0, []],
            ['Institute Campus Plan', 'INSTITUTE_CAMPUS', 'institute', $courseId, 2999, 0, []],
        ];

        foreach ($plans as [$name, $code, $scope, $planCourseId, $price, $trialDays, $subjects]) {
            DB::table('plans')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'scope' => $scope, 'course_id' => $planCourseId, 'billing_interval' => 'month', 'billing_interval_count' => 1, 'price_minor' => $price, 'currency' => 'GBP', 'trial_days' => $trialDays, 'is_public' => true, 'is_active' => true, 'features' => json_encode(['practice_mode' => true, 'analytics' => true, 'ai_explanations' => true]), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
            $planId = DB::table('plans')->where('code', $code)->value('id');
            foreach ($subjects as $subjectId) {
                DB::table('plan_subjects')->updateOrInsert(['plan_id' => $planId, 'subject_id' => $subjectId]);
            }
        }

        $physicsPlanId = DB::table('plans')->where('code', 'PHYSICS_MONTHLY')->value('id');
        $institutePlanId = DB::table('plans')->where('code', 'INSTITUTE_CAMPUS')->value('id');

        DB::table('discounts')->updateOrInsert(
            ['code' => 'WELCOME20'],
            ['name' => 'Welcome 20 Percent', 'discount_type' => 'percentage', 'amount_minor' => null, 'percentage' => 20.0000, 'institute_id' => null, 'plan_id' => null, 'starts_at' => $now->copy()->subDay(), 'ends_at' => $now->copy()->addMonths(3), 'max_redemptions' => 1000, 'redemptions_count' => 0, 'is_active' => true, 'rules' => json_encode(['first_subscription_only' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('discounts')->updateOrInsert(
            ['code' => 'ACADEMY500'],
            ['name' => 'Academy Fixed Discount', 'discount_type' => 'fixed', 'amount_minor' => 500, 'percentage' => null, 'institute_id' => $instituteId, 'plan_id' => $institutePlanId, 'starts_at' => $now->copy()->subDay(), 'ends_at' => $now->copy()->addYear(), 'max_redemptions' => null, 'redemptions_count' => 0, 'is_active' => true, 'rules' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('institute_plan_prices')->updateOrInsert(
            ['institute_id' => $instituteId, 'plan_id' => $institutePlanId, 'starts_at' => null],
            ['price_minor' => 2499, 'currency' => 'GBP', 'ends_at' => null, 'is_active' => true, 'metadata' => json_encode(['negotiated' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('institute_commission_rules')->updateOrInsert(
            ['institute_id' => $instituteId, 'plan_id' => $institutePlanId, 'commission_type' => 'percentage'],
            ['fixed_amount_minor' => null, 'percentage' => 15.0000, 'currency' => 'GBP', 'starts_at' => $now->copy()->subMonth(), 'ends_at' => null, 'is_active' => true, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        $platformUserId = DB::table('users')->where('email', 'student.platform@aistar.test')->value('id');
        $instituteUserId = DB::table('users')->where('email', 'student.institute@aistar.test')->value('id');

        $this->seedSubscription($platformUserId, $physicsPlanId, null, 1499, $physicsId, 'SUB-PLATFORM-001', $adminId, $now);
        $this->seedSubscription($instituteUserId, $institutePlanId, $instituteId, 2499, null, 'SUB-INSTITUTE-001', $adminId, $now);
    }

    private function seedSubscription(int $studentUserId, int $planId, ?int $instituteId, int $amount, ?int $subjectId, string $providerId, int $adminId, $now): void
    {
        DB::table('subscriptions')->updateOrInsert(
            ['provider_subscription_id' => $providerId],
            ['subscriber_type' => 'student', 'subscriber_id' => $studentUserId, 'plan_id' => $planId, 'institute_id' => $instituteId, 'status' => 'active', 'provider' => 'seed', 'trial_ends_at' => null, 'current_period_starts_at' => $now->copy()->startOfMonth(), 'current_period_ends_at' => $now->copy()->addMonth()->startOfMonth(), 'cancel_at_period_end' => false, 'canceled_at' => null, 'ended_at' => null, 'created_by' => $adminId, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );
        $subscriptionId = DB::table('subscriptions')->where('provider_subscription_id', $providerId)->value('id');
        DB::table('subscription_items')->updateOrInsert(
            ['subscription_id' => $subscriptionId, 'plan_id' => $planId, 'subject_id' => $subjectId],
            ['quantity' => 1, 'unit_price_minor' => $amount, 'currency' => 'GBP', 'entitlements' => json_encode(['practice' => true, 'analytics' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        $invoiceNumber = 'INV-' . str_replace('SUB-', '', $providerId);
        DB::table('invoices')->updateOrInsert(
            ['invoice_number' => $invoiceNumber],
            ['subscription_id' => $subscriptionId, 'billable_type' => 'student', 'billable_id' => $studentUserId, 'status' => 'paid', 'subtotal_minor' => $amount, 'discount_minor' => 0, 'tax_minor' => 0, 'total_minor' => $amount, 'paid_minor' => $amount, 'currency' => 'GBP', 'issued_at' => $now->copy()->subDays(2), 'due_at' => $now->copy()->subDay(), 'paid_at' => $now->copy()->subDay(), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );
        $invoiceId = DB::table('invoices')->where('invoice_number', $invoiceNumber)->value('id');
        DB::table('invoice_items')->updateOrInsert(
            ['invoice_id' => $invoiceId, 'description' => 'Monthly subscription'],
            ['quantity' => 1, 'unit_price_minor' => $amount, 'line_total_minor' => $amount, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );
        $paymentProviderId = 'PAY-' . str_replace('SUB-', '', $providerId);
        DB::table('payments')->updateOrInsert(
            ['provider_payment_id' => $paymentProviderId],
            ['invoice_id' => $invoiceId, 'payer_type' => 'student', 'payer_id' => $studentUserId, 'provider' => 'seed', 'status' => 'succeeded', 'amount_minor' => $amount, 'currency' => 'GBP', 'paid_at' => $now->copy()->subDay(), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        if ($instituteId !== null) {
            $paymentId = DB::table('payments')->where('provider_payment_id', $paymentProviderId)->value('id');
            $ruleId = DB::table('institute_commission_rules')->where('institute_id', $instituteId)->where('plan_id', $planId)->value('id');
            DB::table('institute_commissions')->updateOrInsert(
                ['institute_id' => $instituteId, 'subscription_id' => $subscriptionId, 'payment_id' => $paymentId],
                ['student_user_id' => $studentUserId, 'commission_rule_id' => $ruleId, 'status' => 'available', 'base_amount_minor' => $amount, 'commission_amount_minor' => (int) round($amount * 0.15), 'currency' => 'GBP', 'earned_at' => $now->copy()->subDay(), 'available_at' => $now, 'paid_at' => null, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
