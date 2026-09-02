<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingAppleIapSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $planId = DB::table('plans')->where('code', 'PHYSICS_MONTHLY')->value('id');
        if (!$planId) return;

        DB::table('apple_iap_products')->updateOrInsert(
            ['product_id' => 'com.preppy.physics.monthly.test'],
            ['plan_id' => $planId, 'subscription_group_id' => 'preppy-test-subscriptions', 'is_active' => true, 'metadata' => json_encode(['seeded' => true, 'environment' => 'Sandbox']), 'created_at' => $now, 'updated_at' => $now]
        );

        $userId = DB::table('users')->where('email', 'student.platform@preppy.test')->value('id');
        $profileId = DB::table('student_profiles')->where('user_id', $userId)->value('id');
        $subscriptionId = DB::table('subscriptions')->where('subscriber_type', 'student')->where('subscriber_id', $userId)->value('id');
        $invoiceId = $subscriptionId ? DB::table('invoices')->where('subscription_id', $subscriptionId)->value('id') : null;
        $paymentId = $invoiceId ? DB::table('payments')->where('invoice_id', $invoiceId)->value('id') : null;

        DB::table('apple_iap_transactions')->updateOrInsert(
            ['transaction_id' => '2000000000000001'],
            ['user_id' => $userId, 'student_profile_id' => $profileId, 'plan_id' => $planId, 'subscription_id' => $subscriptionId, 'payment_id' => $paymentId, 'product_id' => 'com.preppy.physics.monthly.test', 'original_transaction_id' => '2000000000000001', 'environment' => 'Sandbox', 'status' => 'verified', 'purchased_at' => $now->copy()->subDay(), 'expires_at' => $now->copy()->addMonth(), 'revoked_at' => null, 'raw_payload' => json_encode(['seeded' => true, 'note' => 'Synthetic sandbox record; not an App Store receipt']), 'created_at' => $now, 'updated_at' => $now]
        );
    }
}
