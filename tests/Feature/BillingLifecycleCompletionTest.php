<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteCommissionRule;
use App\Models\Plan;
use App\Models\Payment;
use App\Models\User;
use App\Services\BillingDunningService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use App\Services\SubscriptionEntitlementService;
use App\Services\SubscriptionService;
use App\Services\DevelopmentPaymentBypassService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BillingLifecycleCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_bypass_activates_without_creating_a_provider_payment(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('payments.development_bypass', true);
        [$student, $plan] = $this->studentPlan();

        $response = $this->actingAs($student, 'sanctum')
            ->withHeader('Idempotency-Key', 'development-bypass-checkout')
            ->postJson('/api/v1/checkout/subscriptions', [
                'student_profile_id' => $student->studentProfile->id,
                'plan_id' => $plan->id,
                'provider' => 'google_pay',
            ])
            ->assertCreated()
            ->assertJsonPath('data.requires_payment', false)
            ->assertJsonPath('data.gateway.type', 'development_bypass')
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.invoice.status', 'waived');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('subscriptions', ['id' => $response->json('data.subscription.id'), 'status' => 'active']);
        $this->assertDatabaseHas('invoices', ['id' => $response->json('data.invoice.id'), 'status' => 'waived', 'paid_minor' => 0]);
        config()->set('payments.development_bypass', false);
    }

    public function test_development_bypass_is_ignored_outside_safe_environments(): void
    {
        config()->set('payments.development_bypass', true);
        $original = app()->environment();
        try {
            app()->detectEnvironment(fn () => 'production');
            $this->assertFalse(app(DevelopmentPaymentBypassService::class)->enabled());
        } finally {
            app()->detectEnvironment(fn () => $original);
            config()->set('payments.development_bypass', false);
        }
    }

    public function test_checkout_is_idempotent_and_subscription_activates_only_after_capture(): void
    {
        $this->seed(RolePermissionSeeder::class); $this->googleConfig();
        [$student, $plan] = $this->studentPlan();
        $payload = ['student_profile_id' => $student->studentProfile->id, 'plan_id' => $plan->id, 'provider' => 'google_pay'];
        $first = $this->actingAs($student, 'sanctum')->withHeader('Idempotency-Key', 'same-checkout')->postJson('/api/v1/checkout/subscriptions', $payload)->assertCreated();
        $second = $this->withHeader('Idempotency-Key', 'same-checkout')->postJson('/api/v1/checkout/subscriptions', $payload)->assertOk();
        $this->assertSame($first->json('data.subscription.id'), $second->json('data.subscription.id'));
        $this->assertSame($first->json('data.payment.id'), $second->json('data.payment.id'));
        $this->assertDatabaseHas('subscriptions', ['id' => $first->json('data.subscription.id'), 'status' => 'incomplete']);
        Http::fake(['https://processor.example/capture' => Http::response(['success' => true, 'transaction_id' => 'GP-IDEMPOTENT'], 200)]);
        $this->postJson('/api/v1/payments/'.$first->json('data.payment.id').'/capture/google-pay', ['payment_token' => 'token'])->assertOk();
        $this->postJson('/api/v1/payments/'.$first->json('data.payment.id').'/capture/google-pay', ['payment_token' => 'same-token'])->assertOk();
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('subscriptions', ['id' => $first->json('data.subscription.id'), 'status' => 'active']);
    }

    public function test_plan_trial_configuration_does_not_put_new_subscriptions_into_trial(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$student, $plan] = $this->studentPlan();
        $plan->update(['trial_days' => 14]);

        $subscription = app(SubscriptionService::class)->subscribeStudent(
            $student->studentProfile,
            $plan->fresh(),
            $student->id,
        );

        $this->assertSame('incomplete', $subscription->status->value);
        $this->assertNull($subscription->trial_ends_at);
    }

    public function test_entitlements_allow_active_and_trial_users_but_block_unpaid_students(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$student, $plan] = $this->studentPlan();
        $service = app(SubscriptionEntitlementService::class);
        $this->assertFalse($service->allows($student, 'learning'));
        $this->assertFalse($service->allows($student, 'dashboard'));
        $this->actingAs($student, 'sanctum')->getJson('/api/v1/billing/entitlements?feature=dashboard')
            ->assertOk()->assertJsonPath('data.allowed', false);
        $progress = ['session_type' => 'learn', 'subject' => 'Physics', 'time_seconds' => 10, 'questions' => []];
        $this->putJson('/api/v1/learning-sessions/entitlement-test/progress', $progress)->assertStatus(402);
        $subscription = app(SubscriptionService::class)->subscribeStudent($student->studentProfile, $plan, $student->id);
        $this->assertFalse($service->allows($student->fresh(), 'learning'));
        $this->assertFalse($service->allows($student->fresh(), 'dashboard'));
        $subscription->update(['status' => 'active']);
        $this->assertTrue($service->allows($student->fresh(), 'learning'));
        $this->assertTrue($service->allows($student->fresh(), 'dashboard'));
        $this->getJson('/api/v1/billing/entitlements?feature=dashboard')
            ->assertOk()->assertJsonPath('data.allowed', true);
        $this->putJson('/api/v1/learning-sessions/entitlement-test/progress', $progress)->assertCreated();
        $subscription->update(['status' => 'past_due', 'grace_ends_at' => now()->addDay()]);
        $this->assertTrue($service->allows($student->fresh(), 'learning'));
        $this->assertFalse($service->allows($student->fresh(), 'dashboard'));
        $subscription->update(['grace_ends_at' => now()->subMinute()]);
        $this->assertFalse($service->allows($student->fresh(), 'learning'));
    }

    public function test_dunning_cancels_uncollectible_subscription_and_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$student, $plan] = $this->studentPlan();
        $subscription = app(SubscriptionService::class)->subscribeStudent($student->studentProfile, $plan, $student->id);
        $invoice = app(InvoiceService::class)->createForSubscription($subscription, adjustments: ['due_at' => now()->subDays(20)]);
        $invoice->update(['collection_ends_at' => now()->subDay(), 'next_retry_at' => now()->subHour()]);
        $result = app(BillingDunningService::class)->process();
        $this->assertSame(1, $result['canceled']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'uncollectible']);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'canceled']);
    }

    public function test_due_active_subscription_generates_exactly_one_renewal_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$student, $plan] = $this->studentPlan();
        $subscription = app(SubscriptionService::class)->subscribeStudent($student->studentProfile, $plan, $student->id);
        $subscription->update(['status' => 'active', 'current_period_starts_at' => now()->subMonth(), 'current_period_ends_at' => now()->subMinute()]);
        Artisan::call('billing:renew-subscriptions'); Artisan::call('billing:renew-subscriptions');
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'past_due']);
        $this->assertNotNull($subscription->fresh()->grace_ends_at);
    }

    public function test_failed_payment_is_recorded_and_outstanding_invoice_can_be_retried(): void
    {
        $this->seed(RolePermissionSeeder::class); $this->googleConfig();
        Http::fake(['https://processor.example/capture' => Http::response(['success' => false, 'code' => 'declined', 'message' => 'Declined'], 422)]);
        [$student, $plan] = $this->studentPlan();
        $checkout = $this->actingAs($student, 'sanctum')->withHeader('Idempotency-Key', 'failed-checkout')->postJson('/api/v1/checkout/subscriptions', ['student_profile_id' => $student->studentProfile->id, 'plan_id' => $plan->id, 'provider' => 'google_pay'])->assertCreated();
        $this->postJson('/api/v1/payments/'.$checkout->json('data.payment.id').'/capture/google-pay', ['payment_token' => 'declined-token'])->assertUnprocessable();
        $this->assertDatabaseHas('payments', ['id' => $checkout->json('data.payment.id'), 'status' => 'failed', 'failure_code' => 'declined']);
        $this->assertDatabaseHas('invoices', ['id' => $checkout->json('data.invoice.id'), 'status' => 'issued']);
        $this->withHeader('Idempotency-Key', 'retry-checkout')->postJson('/api/v1/invoices/'.$checkout->json('data.invoice.id').'/checkout', ['provider' => 'google_pay'])->assertCreated()->assertJsonPath('data.payment.attempt_number', 2);
    }

    public function test_invoice_receipt_refund_and_commission_reversal_are_complete(): void
    {
        $this->seed(RolePermissionSeeder::class); $this->googleConfig();
        Http::fake([
            'https://processor.example/capture' => Http::response(['success' => true, 'transaction_id' => 'GP-REFUND'], 200),
            'https://processor.example/refund' => Http::response(['success' => true, 'refund_id' => 'RF-100'], 200),
        ]);
        [$student, $plan, $institute] = $this->studentPlan();
        InstituteCommissionRule::create(['institute_id' => $institute->id, 'plan_id' => $plan->id, 'commission_type' => 'percentage', 'direction' => 'platform_collects_from_institute', 'fixed_amount_minor' => null, 'percentage' => 10, 'currency' => 'PKR', 'is_active' => true, 'metadata' => []]);
        $checkout = $this->actingAs($student, 'sanctum')->withHeader('Idempotency-Key', 'refund-checkout')->postJson('/api/v1/checkout/subscriptions', ['student_profile_id' => $student->studentProfile->id, 'plan_id' => $plan->id, 'provider' => 'google_pay'])->assertCreated();
        $paymentId = $checkout->json('data.payment.id'); $invoiceId = $checkout->json('data.invoice.id');
        $this->postJson("/api/v1/payments/{$paymentId}/capture/google-pay", ['payment_token' => 'token'])->assertOk();
        $this->get("/api/v1/invoices/{$invoiceId}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/api/v1/payments/{$paymentId}/receipt")->assertOk()->assertHeader('content-type', 'application/pdf');

        $admin = User::factory()->create(); app(RoleAssignmentService::class)->assign($admin, 'system_admin');
        $refund = $this->actingAs($admin, 'sanctum')->withHeader('Idempotency-Key', 'refund-once')->postJson("/api/v1/payments/{$paymentId}/refunds", ['amount_minor' => 1000, 'reason' => 'Approved partial refund', 'revoke_entitlement' => false])->assertSuccessful();
        $refundId = $refund->json('data.id');
        $this->withHeader('Idempotency-Key', 'refund-once')->postJson("/api/v1/payments/{$paymentId}/refunds", ['amount_minor' => 1000, 'reason' => 'Approved partial refund', 'revoke_entitlement' => false])->assertOk()->assertJsonPath('data.id', $refundId);
        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'partially_refunded', 'refunded_minor' => 1000]);
        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'status' => 'partially_refunded', 'paid_minor' => $plan->price_minor, 'refunded_minor' => 1000]);
        $this->assertSame('partially_refunded', app(PaymentService::class)->complete(Payment::findOrFail($paymentId), 'GP-REFUND')->status);
        $this->assertDatabaseHas('institute_commissions', ['payment_id' => $paymentId, 'status' => 'collected', 'reversed_amount_minor' => 100]);
        $this->assertDatabaseHas('institute_commission_adjustments', ['payment_refund_id' => $refundId, 'adjustment_type' => 'platform_fee_reversal', 'amount_minor' => 100]);
        $this->get("/api/v1/refunds/{$refundId}/receipt")->assertOk()->assertHeader('content-type', 'application/pdf');
        Http::assertSentCount(2);
    }

    private function googleConfig(): void
    {
        config()->set('payments.google_pay', ['environment' => 'TEST', 'merchant_id' => 'merchant-test', 'merchant_name' => 'AiStar Test', 'gateway' => 'example', 'gateway_merchant_id' => 'gateway-test', 'processor_url' => 'https://processor.example/capture', 'processor_secret' => 'secret', 'refund_url' => 'https://processor.example/refund']);
    }

    private function studentPlan(): array
    {
        $institute = Institute::create(['name' => 'Lifecycle School', 'slug' => 'lifecycle-school', 'code' => 'LIFE', 'status' => 'active', 'settings' => []]);
        $student = User::factory()->create(); app(RoleAssignmentService::class)->assign($student, 'student');
        app(StudentOwnershipService::class)->createInstituteStudent($student, $institute, ['status' => 'active']);
        $plan = Plan::create(['name' => 'Monthly', 'code' => 'LIFECYCLE-PKR', 'scope' => 'all_subjects', 'billing_interval' => 'month', 'billing_interval_count' => 1, 'price_minor' => 2500, 'currency' => 'PKR', 'trial_days' => 0, 'is_public' => true, 'is_active' => true, 'features' => [], 'metadata' => []]);
        return [$student->fresh('studentProfile'), $plan, $institute];
    }
}
