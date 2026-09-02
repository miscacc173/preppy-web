<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Services\DevelopmentPaymentBypassService;
use App\Services\ReferralService;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function subscription(Request $request): JsonResponse
    {
        $data = $request->validate(['student_profile_id' => ['required', 'exists:student_profiles,id'], 'plan_id' => ['required', 'exists:plans,id'], 'provider' => ['required', 'in:jazzcash,google_pay,mock'], 'discount_id' => ['nullable', 'exists:discounts,id'], 'promo_code' => ['nullable', 'string', 'max:80'], 'referral_code' => ['nullable', 'string', 'max:32']]);
        $student = StudentProfile::findOrFail($data['student_profile_id']);
        abort_unless($student->user_id === $request->user()->id || $this->canManageStudent($request, $student), 403);
        $idempotencyKey = $this->idempotencyKey($request);
        $existingPayment = Payment::query()->where('idempotency_key', $idempotencyKey)->with('invoice.subscription')->first();
        if ($existingPayment) {
            abort_unless($existingPayment->payer_id === $student->user_id && $existingPayment->provider === $data['provider'], 409, 'The idempotency key was already used for another checkout.');
            $invoice = $existingPayment->invoice;
            abort_unless($invoice && $invoice->subscription, 409, 'The original checkout is incomplete.');
            $checkout = app(PaymentService::class)->initiate($invoice, $data['provider'], $idempotencyKey);
            return response()->json(['data' => ['subscription' => $invoice->subscription->load(['plan', 'items']), 'invoice' => $invoice->load('items'), 'payment' => $checkout['payment'], 'gateway' => $checkout['gateway'], 'requires_payment' => $checkout['payment']->status !== 'succeeded']]);
        }
        $plan = Plan::query()->where('is_active', true)->findOrFail($data['plan_id']);
        $promotion = ! empty($data['promo_code']) ? app(PromotionService::class)->preview($student, $plan, $data['promo_code']) : null;
        if (! empty($data['referral_code'])) app(ReferralService::class)->validateCode($request->user(), $data['referral_code']);

        [$subscription, $invoice] = DB::transaction(function () use ($student, $plan, $request, $data, $idempotencyKey, $promotion) {
            $subscription = app(SubscriptionService::class)->subscribeStudent($student, $plan, $request->user()->id, $data['discount_id'] ?? null, $idempotencyKey)->load(['plan', 'items']);
            $invoice = app(InvoiceService::class)->createForSubscription($subscription, null, null, ['discount_minor' => $promotion['discount_minor'] ?? 0]);
            if ($promotion) {
                app(PromotionService::class)->reserve($promotion, $student->user_id, $subscription, $invoice);
                $metadata = $invoice->metadata ?? [];
                $metadata['promo_code'] = $promotion['code'];
                $metadata['promotion_type'] = $promotion['type'];
                $invoice->update(['metadata' => $metadata]);
            }
            if (! empty($data['referral_code'])) app(ReferralService::class)->attribute($request->user(), $data['referral_code'], $subscription, $invoice);
            return [$subscription, $invoice->fresh()->load('items')];
        });
        $developmentBypass = app(DevelopmentPaymentBypassService::class);
        if ($developmentBypass->enabled() && $data['provider'] !== 'mock') {
            $bypassed = $developmentBypass->activate($subscription, $invoice, $request->user()->id, $idempotencyKey);
            $bypassed['promotion'] = $promotion;
            return response()->json(['data' => $bypassed], 201);
        }
        $checkout = app(PaymentService::class)->initiate($invoice, $data['provider'], $idempotencyKey);
        return response()->json(['data' => ['subscription' => $subscription, 'invoice' => $invoice, 'payment' => $checkout['payment'], 'gateway' => $checkout['gateway'], 'promotion' => $promotion, 'requires_payment' => true]], 201);
    }

    public function invoice(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);
        $data = $request->validate(['provider' => ['required', 'in:jazzcash,google_pay,mock']]);
        $checkout = app(PaymentService::class)->initiate($invoice, $data['provider'], $this->idempotencyKey($request));
        return response()->json(['data' => ['invoice' => $invoice->load('items'), 'payment' => $checkout['payment'], 'gateway' => $checkout['gateway'], 'requires_payment' => true]], 201);
    }

    public function captureGooglePay(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->payer_id === $request->user()->id || $request->user()->hasSystemRole('system_admin'), 403);
        $data = $request->validate(['payment_token' => ['required', 'string', 'max:100000']]);
        return response()->json(['data' => app(PaymentService::class)->captureGooglePay($payment, $data['payment_token'])]);
    }

    public function captureMock(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->payer_id === $request->user()->id || $request->user()->hasSystemRole('system_admin'), 403);
        abort_unless(config('payments.mock.enabled', false) && app()->environment(['local', 'development', 'testing']), 403, 'Mock payments are not enabled.');
        $data = $request->validate(['payment_token' => ['required', 'in:mock_success,mock_decline']]);
        return response()->json(['data' => app(PaymentService::class)->captureMock($payment, $data['payment_token'])]);
    }

    public function showPayment(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->payer_id === $request->user()->id || $request->user()->hasSystemRole('system_admin', 'system_staff'), 403);
        return response()->json(['data' => $payment->load('invoice')]);
    }

    private function canManageStudent(Request $request, StudentProfile $student): bool
    {
        return $request->user()->hasPermission('subscriptions.manage')
            && ($request->user()->hasSystemRole('system_admin', 'system_staff') || ($student->institute_id && $request->user()->hasRole('institute_admin', 'institute', $student->institute_id)));
    }

    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        if ($invoice->billable_type === 'student' && $invoice->billable_id === $request->user()->id) return;
        abort_unless($request->user()->hasSystemRole('system_admin'), 403);
    }

    private function idempotencyKey(Request $request): string
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        abort_if($key === '' || strlen($key) > 100, 422, 'The Idempotency-Key header is required and must be at most 100 characters.');
        return $key;
    }
}
