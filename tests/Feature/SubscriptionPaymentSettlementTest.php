<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteCommissionRule;
use App\Models\Plan;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionPaymentSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_pay_capture_activates_subscription_pays_invoice_and_records_platform_retention(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('payments.google_pay', [
            'environment' => 'TEST', 'merchant_id' => 'merchant-test', 'merchant_name' => 'AiStar Test',
            'gateway' => 'example', 'gateway_merchant_id' => 'gateway-test',
            'processor_url' => 'https://processor.example/capture', 'processor_secret' => 'secret',
        ]);
        Http::fake(['https://processor.example/capture' => Http::response(['success' => true, 'transaction_id' => 'GP-123'], 200)]);
        [$student, $plan, $institute] = $this->studentPlan();
        InstituteCommissionRule::create(['institute_id' => $institute->id, 'plan_id' => $plan->id, 'commission_type' => 'percentage', 'direction' => 'platform_collects_from_institute', 'fixed_amount_minor' => null, 'percentage' => 10, 'currency' => 'PKR', 'is_active' => true, 'metadata' => []]);

        $checkout = $this->actingAs($student, 'sanctum')->withHeader('Idempotency-Key', 'google-retention-checkout')->postJson('/api/v1/checkout/subscriptions', [
            'student_profile_id' => $student->studentProfile->id, 'plan_id' => $plan->id, 'provider' => 'google_pay',
        ])->assertCreated()->assertJsonPath('data.subscription.status', 'incomplete');
        $paymentId = $checkout->json('data.payment.id');
        $subscriptionId = $checkout->json('data.subscription.id');
        $invoiceId = $checkout->json('data.invoice.id');

        $this->postJson("/api/v1/payments/{$paymentId}/capture/google-pay", ['payment_token' => 'encrypted-google-token'])->assertOk()->assertJsonPath('data.status', 'succeeded');
        $this->assertDatabaseHas('subscriptions', ['id' => $subscriptionId, 'status' => 'active']);
        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'status' => 'paid', 'paid_minor' => 2500]);
        $this->assertDatabaseHas('institute_commissions', ['payment_id' => $paymentId, 'direction' => 'platform_collects_from_institute', 'status' => 'collected', 'commission_amount_minor' => 250]);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret') && $request['amount_minor'] === 2500);
    }

    public function test_jazzcash_checkout_and_verified_callback_complete_the_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('payments.jazzcash', ['endpoint' => 'https://sandbox.jazzcash.com.pk/pay', 'merchant_id' => 'MC123', 'password' => 'password', 'integrity_salt' => 'salt', 'return_url' => 'https://app.example/api/v1/payments/callbacks/jazzcash']);
        [$student, $plan] = $this->studentPlan();
        $checkout = $this->actingAs($student, 'sanctum')->withHeader('Idempotency-Key', 'jazzcash-form-checkout')->postJson('/api/v1/checkout/subscriptions', [
            'student_profile_id' => $student->studentProfile->id, 'plan_id' => $plan->id, 'provider' => 'jazzcash',
        ])->assertCreated()
            ->assertJsonPath('data.subscription.status', 'incomplete')
            ->assertJsonPath('data.gateway.type', 'redirect_form')
            ->assertJsonPath('data.gateway.fields.pp_MerchantID', 'MC123')
            ->assertJson(fn ($json) => $json->whereType('data.gateway.fields.pp_SecureHash', 'string')->etc());
        $callback = ['pp_ResponseCode' => '000', 'pp_ResponseMessage' => 'Approved', 'pp_TxnRefNo' => $checkout->json('data.gateway.fields.pp_TxnRefNo'), 'pp_BillReference' => $checkout->json('data.gateway.fields.pp_BillReference'), 'pp_RetreivalReferenceNo' => 'JC-PAID-1'];
        ksort($callback); $callback['pp_SecureHash'] = hash_hmac('sha256', 'salt&'.implode('&', array_values($callback)), 'salt');
        $this->post('/api/v1/payments/callbacks/jazzcash', $callback)->assertRedirect();
        $this->assertDatabaseHas('payments', ['id' => $checkout->json('data.payment.id'), 'status' => 'succeeded', 'provider_payment_id' => 'JC-PAID-1']);
        $this->assertDatabaseHas('subscriptions', ['id' => $checkout->json('data.subscription.id'), 'status' => 'active']);
    }

    public function test_platform_pay_direction_creates_pending_institute_payable(): void
    {
        $this->seed(RolePermissionSeeder::class);
        config()->set('payments.google_pay', ['environment' => 'TEST', 'merchant_id' => 'merchant-test', 'merchant_name' => 'AiStar Test', 'gateway' => 'example', 'gateway_merchant_id' => 'gateway-test', 'processor_url' => 'https://processor.example/capture', 'processor_secret' => null]);
        Http::fake(['https://processor.example/capture' => Http::response(['success' => true, 'transaction_id' => 'GP-PAYABLE'], 200)]);
        [$student, $plan, $institute] = $this->studentPlan();
        InstituteCommissionRule::create(['institute_id' => $institute->id, 'plan_id' => $plan->id, 'commission_type' => 'fixed', 'direction' => 'platform_pays_institute', 'fixed_amount_minor' => 600, 'percentage' => null, 'currency' => 'PKR', 'is_active' => true, 'metadata' => []]);
        $checkout = $this->actingAs($student, 'sanctum')->withHeader('Idempotency-Key', 'google-payable-checkout')->postJson('/api/v1/checkout/subscriptions', ['student_profile_id' => $student->studentProfile->id, 'plan_id' => $plan->id, 'provider' => 'google_pay'])->assertCreated();
        $paymentId = $checkout->json('data.payment.id');
        $this->postJson("/api/v1/payments/{$paymentId}/capture/google-pay", ['payment_token' => 'token'])->assertOk();
        $this->assertDatabaseHas('institute_commissions', ['payment_id' => $paymentId, 'direction' => 'platform_pays_institute', 'status' => 'pending', 'commission_amount_minor' => 600]);
    }

    private function studentPlan(): array
    {
        $institute = Institute::create(['name' => 'Billing School', 'slug' => 'billing-school', 'code' => 'BILL', 'status' => 'active', 'settings' => []]);
        $student = User::factory()->create();
        app(RoleAssignmentService::class)->assign($student, 'student');
        app(StudentOwnershipService::class)->createInstituteStudent($student, $institute, ['status' => 'active']);
        $plan = Plan::create(['name' => 'Monthly', 'code' => 'MONTHLY-PKR', 'scope' => 'all_subjects', 'billing_interval' => 'month', 'billing_interval_count' => 1, 'price_minor' => 2500, 'currency' => 'PKR', 'trial_days' => 0, 'is_public' => true, 'is_active' => true, 'features' => [], 'metadata' => []]);
        return [$student->fresh('studentProfile'), $plan, $institute];
    }
}
