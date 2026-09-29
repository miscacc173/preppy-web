<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreppyBillingLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = $this->uid('admin@preppy.test');
        $this->activePaid('student.platform@preppy.test', 'PHYSICS_MONTHLY', null, 149900, 'SUB-PREPPY-PLATFORM', $adminId, $now->copy()->subDays(10), $now->copy()->addDays(20));

        $instituteId = DB::table('institutes')->where('code', 'PREPPY-DEMO-001')->value('id');
        $instituteSub = $this->activePaid('student.institute@preppy.test', 'INSTITUTE_CAMPUS', $instituteId, 249900, 'SUB-PREPPY-INSTITUTE', $adminId, $now->copy()->subDays(8), $now->copy()->addDays(22));
        // SCHOOL20 is represented on this successful institute checkout: negotiated price PKR 2,499, then 20% promo.
        DB::table('invoices')->where('id', $instituteSub['invoice_id'])->update(['discount_minor' => 49980, 'total_minor' => 199920, 'paid_minor' => 199920]);
        DB::table('payments')->where('id', $instituteSub['payment_id'])->update(['amount_minor' => 199920]);
        $this->seedCommission($instituteSub, $instituteId, 199920, $now);

        // Upgrade example: current subscription has already moved from Physics Monthly to O Level Complete.
        $upgradeSub = $this->activePaid('student.upgrade@preppy.test', 'OLEVEL_COMPLETE', null, 499900, 'SUB-PREPPY-UPGRADED', $adminId, $now->copy()->subDays(2), $now->copy()->addDays(28), [
            'previous_plan_id' => $this->plan('PHYSICS_MONTHLY'), 'plan_change_type' => 'upgrade', 'upgrade_credit_minor' => 100000,
        ]);
        $upgradeSubId = $upgradeSub['subscription_id'];
        DB::table('invoices')->where('subscription_id', $upgradeSubId)->where('invoice_number', 'INV-PREPPY-UPGRADED')->update([
            'subtotal_minor' => 499900, 'discount_minor' => 100000, 'total_minor' => 399900, 'paid_minor' => 399900,
            'metadata' => json_encode(['seeded' => true, 'billing_event' => 'plan_upgrade', 'carry_forward_credit_minor' => 100000, 'promotion_discount_minor' => 0, 'previous_plan_id' => $this->plan('PHYSICS_MONTHLY'), 'target_plan_id' => $this->plan('OLEVEL_COMPLETE')]),
        ]);
        DB::table('payments')->where('provider_payment_id', 'PAY-PREPPY-UPGRADED')->update(['amount_minor' => 399900]);
        $upgradeInvoiceId = DB::table('invoices')->where('invoice_number', 'INV-PREPPY-UPGRADED')->value('id');
        DB::table('invoice_items')->updateOrInsert(['invoice_id' => $upgradeInvoiceId, 'description' => 'Credit carried forward from unused previous plan value'], [
            'quantity' => 1, 'unit_price_minor' => 0, 'line_total_minor' => 0, 'metadata' => json_encode(['type' => 'carry_forward_credit', 'amount_minor' => 100000]), 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Downgrade example: current cycle continues on premium plan, lower plan scheduled for renewal.
        $downgrade = $this->activePaid('student.downgrade@preppy.test', 'ALL_SUBJECTS_PREMIUM', null, 699900, 'SUB-PREPPY-DOWNGRADE', $adminId, $now->copy()->subDays(15), $now->copy()->addDays(15));
        DB::table('subscriptions')->where('id', $downgrade['subscription_id'])->update(['metadata' => json_encode([
            'seeded' => true,
            'scheduled_plan_change' => ['type' => 'downgrade', 'plan_id' => $this->plan('OLEVEL_CORE'), 'scheduled_at' => $now->copy()->subDay()->toIso8601String(), 'scheduled_by' => $adminId, 'effective_at' => $now->copy()->addDays(15)->toIso8601String(), 'price_minor' => 399900, 'currency' => 'PKR'],
        ])]);

        // Renewal example: invoice is issued and awaiting payment for a cycle that is due now.
        $renewal = $this->activePaid('student.renewal@preppy.test', 'OLEVEL_CORE', null, 399900, 'SUB-PREPPY-RENEWAL', $adminId, $now->copy()->subMonth(), $now);
        DB::table('invoices')->updateOrInsert(['invoice_number' => 'INV-PREPPY-RENEWAL-NEXT'], [
            'subscription_id' => $renewal['subscription_id'], 'billable_type' => 'student', 'billable_id' => $this->uid('student.renewal@preppy.test'), 'status' => 'issued',
            'subtotal_minor' => 399900, 'discount_minor' => 0, 'tax_minor' => 0, 'total_minor' => 399900, 'paid_minor' => 0, 'refunded_minor' => 0,
            'retry_count' => 0, 'currency' => 'PKR', 'period_starts_at' => $now, 'period_ends_at' => $now->copy()->addMonth(), 'issued_at' => $now,
            'due_at' => $now->copy()->addDays(3), 'next_retry_at' => $now->copy()->addDays(3), 'collection_ends_at' => $now->copy()->addDays(17), 'paid_at' => null, 'canceled_at' => null,
            'metadata' => json_encode(['seeded' => true, 'generated_by' => 'renewal']), 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function activePaid(string $email, string $planCode, ?int $instituteId, int $amount, string $providerSubId, int $adminId, $periodStart, $periodEnd, array $metadata = []): array
    {
        $now = now(); $uid = $this->uid($email); $planId = $this->plan($planCode);
        DB::table('subscriptions')->updateOrInsert(['provider_subscription_id' => $providerSubId], [
            'subscriber_type' => 'student', 'subscriber_id' => $uid, 'plan_id' => $planId, 'institute_id' => $instituteId,
            'status' => 'active', 'provider' => 'seed', 'checkout_idempotency_key' => 'seed-'.$providerSubId,
            'trial_ends_at' => null, 'grace_ends_at' => null, 'current_period_starts_at' => $periodStart, 'current_period_ends_at' => $periodEnd,
            'cancel_at_period_end' => false, 'canceled_at' => null, 'ended_at' => null, 'created_by' => $adminId,
            'metadata' => json_encode(array_merge(['seeded' => true, 'platform' => 'Preppy'], $metadata)), 'created_at' => $now, 'updated_at' => $now,
        ]);
        $subscriptionId = DB::table('subscriptions')->where('provider_subscription_id', $providerSubId)->value('id');
        $profile = DB::table('student_profiles')->where('user_id', $uid)->first();
        $courseId = $profile?->course_id;
        $subjectIds = DB::table('plan_course_subjects')->where(['plan_id' => $planId, 'course_id' => $courseId])->pluck('subject_id')->map(fn($x)=>(int)$x)->all();
        DB::table('subscription_items')->where('subscription_id', $subscriptionId)->delete();
        DB::table('subscription_items')->insert([
            'subscription_id' => $subscriptionId, 'plan_id' => $planId, 'subject_id' => null, 'quantity' => 1, 'unit_price_minor' => $amount, 'currency' => 'PKR',
            'entitlements' => json_encode(['course_id' => $courseId, 'subject_ids' => $subjectIds, 'seeded' => true]), 'created_at' => $now, 'updated_at' => $now,
        ]);
        $invoiceNumber = 'INV-'.str_replace('SUB-', '', $providerSubId);
        DB::table('invoices')->updateOrInsert(['invoice_number' => $invoiceNumber], [
            'subscription_id' => $subscriptionId, 'billable_type' => 'student', 'billable_id' => $uid, 'status' => 'paid',
            'subtotal_minor' => $amount, 'discount_minor' => 0, 'tax_minor' => 0, 'total_minor' => $amount, 'paid_minor' => $amount, 'refunded_minor' => 0,
            'retry_count' => 0, 'currency' => 'PKR', 'period_starts_at' => $periodStart, 'period_ends_at' => $periodEnd,
            'issued_at' => $periodStart, 'due_at' => $periodStart->copy()->addDays(3), 'next_retry_at' => null, 'collection_ends_at' => null,
            'paid_at' => $periodStart->copy()->addDay(), 'canceled_at' => null, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now,
        ]);
        $invoiceId = DB::table('invoices')->where('invoice_number', $invoiceNumber)->value('id');
        DB::table('invoice_items')->updateOrInsert(['invoice_id' => $invoiceId, 'description' => 'Preppy subscription: '.$planCode], [
            'quantity' => 1, 'unit_price_minor' => $amount, 'line_total_minor' => $amount, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now,
        ]);
        $payId = 'PAY-'.str_replace('SUB-', '', $providerSubId);
        DB::table('payments')->updateOrInsert(['provider_payment_id' => $payId], [
            'invoice_id' => $invoiceId, 'payer_type' => 'student', 'payer_id' => $uid, 'provider' => 'mock', 'checkout_reference' => 'CHK-'.$payId,
            'idempotency_key' => 'IDEMP-'.$payId, 'retry_of_payment_id' => null, 'attempt_number' => 1, 'status' => 'succeeded', 'failure_code' => null, 'failure_message' => null,
            'amount_minor' => $amount, 'refunded_minor' => 0, 'currency' => 'PKR', 'receipt_number' => 'RCT-'.$payId, 'receipt_issued_at' => $periodStart->copy()->addDay(), 'expires_at' => null,
            'paid_at' => $periodStart->copy()->addDay(), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now,
        ]);
        return ['subscription_id' => (int)$subscriptionId, 'invoice_id' => (int)$invoiceId, 'payment_id' => (int)DB::table('payments')->where('provider_payment_id', $payId)->value('id')];
    }

    private function seedCommission(array $sub, int $instituteId, int $amount, $now): void
    {
        $planId = $this->plan('INSTITUTE_CAMPUS');
        $ruleId = DB::table('institute_commission_rules')->where(['institute_id'=>$instituteId,'plan_id'=>$planId])->value('id');
        DB::table('institute_commissions')->updateOrInsert(['institute_id'=>$instituteId,'subscription_id'=>$sub['subscription_id'],'payment_id'=>$sub['payment_id']], [
            'student_user_id'=>$this->uid('student.institute@preppy.test'),'commission_rule_id'=>$ruleId,'direction'=>'platform_pays_institute','status'=>'available',
            'base_amount_minor'=>$amount,'commission_amount_minor'=>(int)round($amount*.15),'reversed_amount_minor'=>0,'currency'=>'PKR','earned_at'=>$now->copy()->subDay(),'available_at'=>$now,
            'paid_at'=>null,'settled_at'=>null,'metadata'=>json_encode(['seeded'=>true]),'created_at'=>$now,'updated_at'=>$now,
        ]);
    }

    private function uid(string $email): int { return (int) DB::table('users')->where('email',$email)->value('id'); }
    private function plan(string $code): int { return (int) DB::table('plans')->where('code',$code)->value('id'); }
}
