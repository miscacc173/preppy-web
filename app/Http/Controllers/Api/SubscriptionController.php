<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\InvoiceService;
use App\Services\DevelopmentPaymentBypassService;
use App\Services\PaymentService;
use App\Services\PromotionService;
use App\Services\ReferralService;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $q = Subscription::with(['plan', 'items']);
        if ($r->user()->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($r->user()->hasPermission('subscriptions.manage'), 403);
        } elseif ($r->user()->instituteContextIds()) {
            abort_unless($r->user()->hasPermission('subscriptions.manage'), 403);
            $q->whereIn('institute_id', $r->user()->instituteContextIds());
        } else {
            $q->where('subscriber_id', $r->user()->id);
        }
        return response()->json($q->latest()->paginate());
    }

    public function store(Request $r): JsonResponse
    {
        $d = $r->validate(['student_profile_id' => 'required|exists:student_profiles,id', 'plan_id' => 'required|exists:plans,id', 'discount_id' => 'nullable|exists:discounts,id']);
        $student = StudentProfile::findOrFail($d['student_profile_id']);
        abort_unless($student->user_id === $r->user()->id || $this->canManageStudent($student), 403);
        $plan = Plan::query()->where('is_active', true)->findOrFail($d['plan_id']);
        $s = app(SubscriptionService::class)->subscribeStudent($student, $plan, $r->user()->id, $d['discount_id'] ?? null);
        $s->load(['plan', 'items']);
        $invoice = app(InvoiceService::class)->createForSubscription($s);
        $developmentBypass = app(DevelopmentPaymentBypassService::class);
        if ($developmentBypass->enabled()) {
            $result = $developmentBypass->activate($s, $invoice, $r->user()->id, 'subscription-create-'.$s->id);
            return response()->json(['data' => $result['subscription'], 'invoice' => $result['invoice'], 'requires_payment' => false], 201);
        }
        return response()->json(['data' => $s, 'invoice' => $invoice], 201);
    }

    public function previewPlanChange(Request $r, Subscription $subscription): JsonResponse
    {
        $this->authorizeSubscription($subscription);
        abort_unless(in_array($subscription->status->value, ['active', 'paused'], true), 422, 'Only an active or paused subscription can change plan.');
        $data = $r->validate(['plan_id' => 'required|exists:plans,id', 'promo_code' => 'nullable|string|max:80']);
        $student = StudentProfile::query()->where('user_id', $subscription->subscriber_id)->firstOrFail();
        $plan = Plan::query()->where('is_active', true)->findOrFail($data['plan_id']);
        $quote = app(SubscriptionService::class)->planChangeQuote($subscription, $student, $plan);
        $promotion = null;
        if ($quote['type'] === 'upgrade' && ! empty($data['promo_code'])) {
            $promotion = app(PromotionService::class)->preview($student, $plan, $data['promo_code'], (int) $quote['subtotal_after_credit_minor']);
        }
        $quote['promotion_discount_minor'] = (int) ($promotion['discount_minor'] ?? 0);
        $quote['total_minor'] = max(0, (int) $quote['subtotal_after_credit_minor'] - $quote['promotion_discount_minor']);
        $quote['promotion'] = $promotion;
        return response()->json(['data' => $quote]);
    }

    public function changePlan(Request $r, Subscription $subscription): JsonResponse
    {
        $this->authorizeSubscription($subscription);
        abort_unless(in_array($subscription->status->value, ['active', 'paused'], true), 422, 'Only an active or paused subscription can change plan.');

        $data = $r->validate([
            'plan_id' => 'required|exists:plans,id',
            'provider' => 'nullable|in:jazzcash,google_pay,mock',
            'promo_code' => 'nullable|string|max:80',
            'referral_code' => 'nullable|string|max:32',
        ]);
        $student = StudentProfile::query()->where('user_id', $subscription->subscriber_id)->firstOrFail();
        $plan = Plan::query()->where('is_active', true)->findOrFail($data['plan_id']);
        $service = app(SubscriptionService::class);
        $quote = $service->planChangeQuote($subscription, $student, $plan);

        if ($quote['type'] === 'downgrade') {
            $updated = $service->scheduleDowngrade($subscription, $student, $plan, $r->user()->id);
            return response()->json(['data' => $updated, 'change' => $quote, 'requires_payment' => false]);
        }

        $provider = $data['provider'] ?? null;
        abort_unless($provider, 422, 'A payment provider is required for an upgrade.');
        $idempotencyKey = trim((string) $r->header('Idempotency-Key'));
        abort_if($idempotencyKey === '' || strlen($idempotencyKey) > 100, 422, 'The Idempotency-Key header is required and must be at most 100 characters.');

        $existingPayment = Payment::query()->where('idempotency_key', $idempotencyKey)->with('invoice.subscription')->first();
        if ($existingPayment) {
            abort_unless($existingPayment->invoice?->subscription_id === $subscription->id && $existingPayment->provider === $provider, 409, 'The idempotency key was already used for another checkout.');
            $checkout = app(PaymentService::class)->initiate($existingPayment->invoice, $provider, $idempotencyKey);
            return response()->json(['data' => $subscription->fresh()->load(['plan', 'items']), 'invoice' => $existingPayment->invoice->load('items'), 'payment' => $checkout['payment'], 'gateway' => $checkout['gateway'], 'change' => $quote, 'requires_payment' => $checkout['payment']->status !== 'succeeded']);
        }

        $openUpgradeInvoice = Invoice::query()->where('subscription_id', $subscription->id)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->latest('id')->get()->first(fn (Invoice $candidate) => data_get($candidate->metadata, 'billing_event') === 'plan_upgrade');
        if ($openUpgradeInvoice) {
            abort_unless((int) data_get($openUpgradeInvoice->metadata, 'target_plan_id') === (int) $plan->id, 409, 'Another plan upgrade is awaiting payment. Complete or let that invoice expire before selecting a different upgrade.');
            $checkout = app(PaymentService::class)->initiate($openUpgradeInvoice, $provider, $idempotencyKey);
            return response()->json(['data' => $subscription->fresh()->load(['plan', 'items']), 'invoice' => $openUpgradeInvoice->load('items'), 'payment' => $checkout['payment'], 'gateway' => $checkout['gateway'], 'change' => $quote, 'requires_payment' => $checkout['payment']->status !== 'succeeded']);
        }

        $promotion = ! empty($data['promo_code'])
            ? app(PromotionService::class)->preview($student, $plan, $data['promo_code'], (int) $quote['subtotal_after_credit_minor'])
            : null;
        if (! empty($data['referral_code'])) app(ReferralService::class)->validateCode($r->user(), $data['referral_code']);

        $invoice = app(InvoiceService::class)->createForPlanUpgrade($subscription, $plan, $quote, (int) ($promotion['discount_minor'] ?? 0));
        if ($promotion) {
            app(PromotionService::class)->reserve($promotion, $student->user_id, $subscription, $invoice);
            $meta = $invoice->metadata ?? [];
            $meta['promo_code'] = $promotion['code'];
            $meta['promotion_type'] = $promotion['type'];
            $invoice->update(['metadata' => $meta]);
        }
        if (! empty($data['referral_code'])) app(ReferralService::class)->attribute($r->user(), $data['referral_code'], $subscription, $invoice);

        $developmentBypass = app(DevelopmentPaymentBypassService::class);
        if ($developmentBypass->enabled() && $provider !== 'mock') {
            $result = $developmentBypass->activate($subscription, $invoice, $r->user()->id, $idempotencyKey);
            $result['change'] = $quote;
            $result['promotion'] = $promotion;
            return response()->json(['data' => $result['subscription'], 'invoice' => $result['invoice'], 'payment' => null, 'gateway' => $result['gateway'], 'change' => $quote, 'promotion' => $promotion, 'requires_payment' => false]);
        }

        $checkout = app(PaymentService::class)->initiate($invoice, $provider, $idempotencyKey);
        return response()->json(['data' => $subscription->fresh()->load(['plan', 'items']), 'invoice' => $invoice, 'payment' => $checkout['payment'], 'gateway' => $checkout['gateway'], 'change' => $quote, 'promotion' => $promotion, 'requires_payment' => true], 201);
    }

    public function cancel(Subscription $subscription): JsonResponse
    {
        $this->authorizeSubscription($subscription);
        $subscription->update(['cancel_at_period_end' => true, 'canceled_at' => now()]);
        return response()->json(['data' => $subscription->fresh()]);
    }

    public function resume(Subscription $subscription): JsonResponse
    {
        $this->authorizeSubscription($subscription);
        $subscription->update(['cancel_at_period_end' => false, 'canceled_at' => null]);
        return response()->json(['data' => $subscription->fresh()]);
    }

    public function pause(Subscription $subscription): JsonResponse
    {
        $this->authorizeSubscription($subscription);
        $subscription->update(['status' => 'paused']);
        return response()->json(['data' => $subscription->fresh()]);
    }

    public function activate(Subscription $subscription): JsonResponse
    {
        $this->authorizeSubscription($subscription);
        if ($subscription->status->value !== 'paused') {
            $developmentBypass = app(DevelopmentPaymentBypassService::class);
            if ($developmentBypass->enabled()) {
                $invoice = $subscription->invoices()->latest('id')->first()
                    ?? app(InvoiceService::class)->createForSubscription($subscription);
                $result = $developmentBypass->activate($subscription, $invoice, request()->user()->id, 'subscription-activate-'.$subscription->id);
                return response()->json(['data' => $result['subscription']]);
            }
            $paid = $subscription->invoices()->where('status', 'paid')->exists();
            abort_unless($paid || request()->user()->hasSystemRole('system_admin'), 422, 'Payment is required before activation.');
        }
        $subscription->update(['status' => 'active']);
        return response()->json(['data' => $subscription->fresh()]);
    }

    private function authorizeSubscription(Subscription $subscription): void
    {
        $user = request()->user();
        if ($subscription->subscriber_id === $user->id) return;
        abort_unless($user->hasPermission('subscriptions.manage'), 403);
        if (! $user->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($subscription->institute_id && in_array($subscription->institute_id, $user->instituteContextIds(), true), 403);
        }
    }

    private function canManageStudent(StudentProfile $student): bool
    {
        $user = request()->user();
        if (! $user->hasPermission('subscriptions.manage')) return false;
        return $user->hasSystemRole('system_admin', 'system_staff')
            || ($student->institute_id && in_array($student->institute_id, $user->instituteContextIds(), true));
    }
}
