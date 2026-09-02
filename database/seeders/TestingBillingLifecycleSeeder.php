<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingBillingLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@preppy.test')->value('id');
        $instituteId = DB::table('institutes')->where('code', 'PREPPY-DEMO-001')->value('id');
        $studentId = DB::table('users')->where('email', 'student.institute@preppy.test')->value('id');
        $subscriptionId = DB::table('subscriptions')->where('subscriber_type', 'student')->where('subscriber_id', $studentId)->value('id');
        $invoiceId = $subscriptionId ? DB::table('invoices')->where('subscription_id', $subscriptionId)->value('id') : null;
        $paymentId = $invoiceId ? DB::table('payments')->where('invoice_id', $invoiceId)->value('id') : null;
        $commissionId = $paymentId ? DB::table('institute_commissions')->where('payment_id', $paymentId)->value('id') : null;

        if ($commissionId) {
            $commission = DB::table('institute_commissions')->where('id', $commissionId)->first();
            DB::table('commission_payouts')->updateOrInsert(
                ['reference' => 'SEED-PAYOUT-001'],
                ['institute_id' => $instituteId, 'status' => 'paid', 'amount_minor' => $commission->commission_amount_minor, 'currency' => $commission->currency, 'period_starts_at' => $now->copy()->subMonth()->startOfMonth(), 'period_ends_at' => $now->copy()->subMonth()->endOfMonth(), 'paid_at' => $now->copy()->subHours(12), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
            $payoutId = DB::table('commission_payouts')->where('reference', 'SEED-PAYOUT-001')->value('id');
            DB::table('commission_payout_items')->updateOrInsert(['commission_payout_id' => $payoutId, 'institute_commission_id' => $commissionId]);
            DB::table('institute_commissions')->where('id', $commissionId)->update(['status' => 'paid', 'paid_at' => $now->copy()->subHours(12), 'settled_at' => $now->copy()->subHours(12), 'updated_at' => $now]);
        }

        if ($paymentId && $invoiceId) {
            $refundAmount = 250;
            DB::table('payment_refunds')->updateOrInsert(
                ['idempotency_key' => 'seed-refund-001'],
                ['payment_id' => $paymentId, 'invoice_id' => $invoiceId, 'provider_refund_id' => 'SEED-REFUND-001', 'status' => 'succeeded', 'amount_minor' => $refundAmount, 'currency' => 'GBP', 'reason' => 'Seeded partial refund scenario', 'initiated_by' => $adminId, 'failure_code' => null, 'failure_message' => null, 'processed_at' => $now, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
            $refundId = DB::table('payment_refunds')->where('idempotency_key', 'seed-refund-001')->value('id');
            DB::table('payments')->where('id', $paymentId)->update(['refunded_minor' => $refundAmount, 'updated_at' => $now]);
            DB::table('invoices')->where('id', $invoiceId)->update(['refunded_minor' => $refundAmount, 'updated_at' => $now]);

            if ($commissionId) {
                DB::table('institute_commission_adjustments')->updateOrInsert(
                    ['institute_commission_id' => $commissionId, 'payment_refund_id' => $refundId],
                    ['adjustment_type' => 'refund_reversal', 'amount_minor' => 38, 'currency' => 'GBP', 'status' => 'applied', 'applied_at' => $now, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
                );
                DB::table('institute_commissions')->where('id', $commissionId)->update(['reversed_amount_minor' => 38, 'updated_at' => $now]);
            }
        }
    }
}
