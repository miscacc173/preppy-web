<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingPromotionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $instituteId = DB::table('institutes')->where('code', 'PREPPY-DEMO-001')->value('id');
        $planId = DB::table('plans')->where('code', 'INSTITUTE_CAMPUS')->value('id');
        $studentId = DB::table('users')->where('email', 'student.institute@preppy.test')->value('id');
        $referrerId = DB::table('users')->where('email', 'student.platform@preppy.test')->value('id');
        $referredId = DB::table('users')->where('email', 'student.institute2@preppy.test')->value('id');

        DB::table('institute_promo_codes')->updateOrInsert(
            ['code' => 'SCHOOL20'],
            ['institute_id' => $instituteId, 'name' => 'Preppy Demo School 20%', 'discount_type' => 'percentage', 'amount_minor' => null, 'percentage' => 20.0000, 'starts_at' => $now->copy()->subDay(), 'ends_at' => $now->copy()->addMonths(6), 'max_redemptions' => 500, 'per_user_limit' => 1, 'redemptions_count' => 0, 'is_active' => true, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );
        $promoId = DB::table('institute_promo_codes')->where('code', 'SCHOOL20')->value('id');
        DB::table('institute_promo_code_plans')->updateOrInsert(['institute_promo_code_id' => $promoId, 'plan_id' => $planId]);

        DB::table('referral_codes')->updateOrInsert(
            ['user_id' => $referrerId],
            ['code' => 'FRIENDDEMO', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
        );
        $referralCodeId = DB::table('referral_codes')->where('user_id', $referrerId)->value('id');
        $subscriptionId = DB::table('subscriptions')->where('subscriber_type', 'student')->where('subscriber_id', $referredId)->value('id');
        $invoiceId = $subscriptionId ? DB::table('invoices')->where('subscription_id', $subscriptionId)->value('id') : null;
        $paymentId = $invoiceId ? DB::table('payments')->where('invoice_id', $invoiceId)->value('id') : null;

        DB::table('referrals')->updateOrInsert(
            ['referred_user_id' => $referredId],
            ['referrer_user_id' => $referrerId, 'referral_code_id' => $referralCodeId, 'subscription_id' => $subscriptionId, 'invoice_id' => $invoiceId, 'qualifying_payment_id' => $paymentId, 'reward_discount_id' => null, 'status' => $paymentId ? 'qualified' : 'pending', 'qualified_at' => $paymentId ? $now : null, 'rewarded_at' => null, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        $subId = DB::table('subscriptions')->where('subscriber_type', 'student')->where('subscriber_id', $studentId)->value('id');
        $invId = $subId ? DB::table('invoices')->where('subscription_id', $subId)->value('id') : null;
        $payId = $invId ? DB::table('payments')->where('invoice_id', $invId)->value('id') : null;
        if ($invId) {
            DB::table('promotion_redemptions')->updateOrInsert(
                ['invoice_id' => $invId],
                ['promotion_type' => 'institute_promo', 'promotion_id' => $promoId, 'code' => 'SCHOOL20', 'user_id' => $studentId, 'subscription_id' => $subId, 'payment_id' => $payId, 'discount_minor' => 500, 'status' => $payId ? 'consumed' : 'pending', 'expires_at' => $now->copy()->addMonth(), 'consumed_at' => $payId ? $now : null, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
