<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function subscribeStudent(StudentProfile $student, Plan $plan, int $actorId, ?int $discountId = null, ?string $checkoutIdempotencyKey = null): Subscription
    {
        app(PlanEligibilityService::class)->ensureApplicable($student, $plan);

        return DB::transaction(function () use ($student, $plan, $actorId, $discountId, $checkoutIdempotencyKey) {
            if ($checkoutIdempotencyKey) {
                $existing = Subscription::query()->where('checkout_idempotency_key', $checkoutIdempotencyKey)->lockForUpdate()->first();
                if ($existing) {
                    abort_unless($existing->subscriber_id === $student->user_id && $existing->plan_id === $plan->id, 409, 'The idempotency key was already used for another subscription.');
                    return $existing;
                }
            }
            $live = Subscription::where(['subscriber_type' => 'student', 'subscriber_id' => $student->user_id])->whereIn('status', ['incomplete', 'trialing', 'active', 'past_due', 'paused'])->lockForUpdate()->exists();
            if ($live) throw ValidationException::withMessages(['subscription' => 'Student already has a live subscription.']);
            $pricing = app(PricingService::class)->priceFor($plan, $student->institute_id);
            $start = now();
            $end = $this->periodEnd($plan, $start);
            $subscription = Subscription::create(['subscriber_type' => 'student', 'subscriber_id' => $student->user_id, 'plan_id' => $plan->id, 'institute_id' => $student->institute_id, 'status' => SubscriptionStatus::Incomplete, 'checkout_idempotency_key' => $checkoutIdempotencyKey, 'trial_ends_at' => null, 'current_period_starts_at' => $start, 'current_period_ends_at' => $end, 'created_by' => $actorId, 'metadata' => ['discount_id' => $discountId]]);
            $subscription->items()->create(['plan_id' => $plan->id, 'quantity' => 1, 'unit_price_minor' => $pricing['price_minor'], 'currency' => $pricing['currency'], 'entitlements' => $this->entitlements($plan, $student)]);
            return $subscription;
        });
    }

    /** @return array<string,mixed> */
    public function planChangeQuote(Subscription $subscription, StudentProfile $student, Plan $plan): array
    {
        app(PlanEligibilityService::class)->ensureApplicable($student, $plan);
        $subscription->loadMissing(['items', 'plan', 'invoices']);
        abort_unless($subscription->subscriber_type === 'student' && (int) $subscription->subscriber_id === (int) $student->user_id, 422, 'The subscription does not belong to this student.');
        abort_if((int) $subscription->plan_id === (int) $plan->id, 422, 'The selected plan is already active.');

        $currentItem = $subscription->items->first();
        $currentPrice = (int) ($currentItem?->unit_price_minor ?? 0);
        $targetPricing = app(PricingService::class)->priceFor($plan, $student->institute_id);
        $targetPrice = (int) $targetPricing['price_minor'];
        $type = $targetPrice > $currentPrice ? 'upgrade' : 'downgrade';

        $quote = [
            'type' => $type,
            'current_plan_id' => (int) $subscription->plan_id,
            'target_plan_id' => (int) $plan->id,
            'current_price_minor' => $currentPrice,
            'target_price_minor' => $targetPrice,
            'currency' => strtoupper((string) $targetPricing['currency']),
            'effective_at' => ($type === 'upgrade' ? now() : $subscription->current_period_ends_at)->toIso8601String(),
        ];

        if ($type === 'downgrade') {
            return $quote + ['unused_credit_minor' => 0, 'subtotal_after_credit_minor' => $targetPrice];
        }

        $start = $subscription->current_period_starts_at;
        $end = $subscription->current_period_ends_at;
        $totalSeconds = max(1, $start && $end ? $start->diffInSeconds($end) : 1);
        $remainingSeconds = ($end && $end->isFuture()) ? now()->diffInSeconds($end) : 0;
        $sourceInvoice = $this->currentCycleInvoice($subscription);
        $creditBase = $sourceInvoice && in_array($sourceInvoice->status, ['paid', 'closed'], true)
            ? min($currentPrice, (int) $sourceInvoice->total_minor)
            : $currentPrice;
        $credit = (int) round($creditBase * ($remainingSeconds / $totalSeconds));
        $credit = max(0, min($targetPrice, $credit));

        return $quote + [
            'unused_credit_minor' => $credit,
            'credit_base_minor' => $creditBase,
            'remaining_seconds' => $remainingSeconds,
            'cycle_seconds' => $totalSeconds,
            'subtotal_after_credit_minor' => max(0, $targetPrice - $credit),
            'source_invoice_id' => $sourceInvoice?->id,
        ];
    }

    public function scheduleDowngrade(Subscription $subscription, StudentProfile $student, Plan $plan, int $actorId): Subscription
    {
        $quote = $this->planChangeQuote($subscription, $student, $plan);
        abort_unless($quote['type'] === 'downgrade', 422, 'This plan change is not a downgrade.');

        return DB::transaction(function () use ($subscription, $student, $plan, $actorId, $quote): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $pricing = app(PricingService::class)->priceFor($plan, $student->institute_id);
            $metadata = $locked->metadata ?? [];
            $metadata['scheduled_plan_change'] = [
                'type' => 'downgrade', 'plan_id' => $plan->id, 'scheduled_at' => now()->toIso8601String(),
                'scheduled_by' => $actorId, 'effective_at' => $locked->current_period_ends_at?->toIso8601String(),
                'price_minor' => (int) $pricing['price_minor'], 'currency' => strtoupper((string) $pricing['currency']),
            ];
            $locked->update(['metadata' => $metadata]);
            return $locked->fresh()->load(['plan', 'items']);
        });
    }

    public function finalizeUpgradeFromInvoice(Subscription $subscription, Invoice $invoice): Subscription
    {
        $metadata = $invoice->metadata ?? [];
        abort_unless(($metadata['billing_event'] ?? null) === 'plan_upgrade', 422, 'This invoice is not a plan upgrade invoice.');

        return DB::transaction(function () use ($subscription, $invoice, $metadata): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $targetPlan = Plan::findOrFail((int) $metadata['target_plan_id']);
            $start = $invoice->period_starts_at ?? now();
            $end = $invoice->period_ends_at ?? $this->periodEnd($targetPlan, $start);

            if ($sourceInvoiceId = ($metadata['source_invoice_id'] ?? null)) {
                $sourceInvoice = Invoice::query()->lockForUpdate()->find($sourceInvoiceId);
                if ($sourceInvoice && $sourceInvoice->id !== $invoice->id && $sourceInvoice->status !== 'closed') {
                    $sourceMeta = $sourceInvoice->metadata ?? [];
                    $sourceMeta['closed_reason'] = 'plan_upgrade';
                    $sourceMeta['closed_at'] = now()->toIso8601String();
                    $sourceMeta['previous_status'] = $sourceInvoice->status;
                    $sourceMeta['upgraded_to_plan_id'] = $targetPlan->id;
                    $sourceInvoice->update(['status' => 'closed', 'canceled_at' => now(), 'metadata' => $sourceMeta]);
                }
            }

            $subscriptionMeta = $locked->metadata ?? [];
            unset($subscriptionMeta['scheduled_plan_change']);
            $subscriptionMeta['previous_plan_id'] = (int) ($metadata['previous_plan_id'] ?? $locked->plan_id);
            $subscriptionMeta['plan_changed_at'] = now()->toIso8601String();
            $subscriptionMeta['plan_change_type'] = 'upgrade';
            $subscriptionMeta['upgrade_invoice_id'] = $invoice->id;
            $subscriptionMeta['upgrade_credit_minor'] = (int) ($metadata['carry_forward_credit_minor'] ?? 0);

            $locked->update([
                'plan_id' => $targetPlan->id,
                'institute_id' => $locked->institute_id,
                'status' => 'active',
                'current_period_starts_at' => $start,
                'current_period_ends_at' => $end,
                'grace_ends_at' => null,
                'metadata' => $subscriptionMeta,
            ]);

            $this->replaceSubscriptionItem($locked, $targetPlan, (int) $metadata['target_price_minor'], (string) $invoice->currency);
            return $locked->fresh()->load(['plan', 'items']);
        });
    }

    public function applyStoreManagedPlanChange(Subscription $subscription, Plan $plan, CarbonInterface $start, CarbonInterface $end, array $metadata = []): Subscription
    {
        return DB::transaction(function () use ($subscription, $plan, $start, $end, $metadata): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            app(PlanEligibilityService::class)->ensureApplicable(
                StudentProfile::query()->where('user_id', $locked->subscriber_id)->firstOrFail(),
                $plan
            );
            $pricing = app(PricingService::class)->priceFor($plan, $locked->institute_id);
            $subscriptionMeta = $locked->metadata ?? [];
            unset($subscriptionMeta['scheduled_plan_change']);
            $subscriptionMeta['previous_plan_id'] = $locked->plan_id;
            $subscriptionMeta['plan_changed_at'] = now()->toIso8601String();
            $subscriptionMeta['plan_change_type'] = 'store_managed';
            $subscriptionMeta['store_managed'] = array_merge($subscriptionMeta['store_managed'] ?? [], $metadata);
            $locked->update([
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_starts_at' => $start,
                'current_period_ends_at' => $end,
                'grace_ends_at' => null,
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'metadata' => $subscriptionMeta,
            ]);
            $this->replaceSubscriptionItem($locked, $plan, (int) $pricing['price_minor'], (string) $pricing['currency']);
            return $locked->fresh()->load(['plan', 'items']);
        });
    }

    public function applyScheduledPlanChangeAtRenewal(Subscription $subscription): Subscription
    {
        $scheduled = data_get($subscription->metadata, 'scheduled_plan_change');
        if (! is_array($scheduled) || ($scheduled['type'] ?? null) !== 'downgrade' || empty($scheduled['plan_id'])) return $subscription;

        return DB::transaction(function () use ($subscription, $scheduled): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $currentScheduled = data_get($locked->metadata, 'scheduled_plan_change');
            if (! is_array($currentScheduled) || empty($currentScheduled['plan_id'])) return $locked->fresh(['plan', 'items']);
            $plan = Plan::findOrFail((int) $currentScheduled['plan_id']);
            $metadata = $locked->metadata ?? [];
            $metadata['previous_plan_id'] = $locked->plan_id;
            $metadata['plan_changed_at'] = now()->toIso8601String();
            $metadata['plan_change_type'] = 'downgrade';
            $metadata['scheduled_change_applied_at'] = now()->toIso8601String();
            unset($metadata['scheduled_plan_change']);
            $locked->update(['plan_id' => $plan->id, 'metadata' => $metadata]);
            $this->replaceSubscriptionItem($locked, $plan, (int) $currentScheduled['price_minor'], (string) $currentScheduled['currency']);
            return $locked->fresh(['plan', 'items']);
        });
    }

    public function periodEnd(Plan $plan, CarbonInterface $start): CarbonInterface
    {
        return match ($plan->billing_interval) {
            'day' => $start->copy()->addDays($plan->billing_interval_count),
            'week' => $start->copy()->addWeeks($plan->billing_interval_count),
            'quarter' => $start->copy()->addMonths(3 * $plan->billing_interval_count),
            'year' => $start->copy()->addYears($plan->billing_interval_count),
            default => $start->copy()->addMonths($plan->billing_interval_count),
        };
    }

    private function currentCycleInvoice(Subscription $subscription): ?Invoice
    {
        if (! $subscription->current_period_starts_at) return null;
        return Invoice::query()->where('subscription_id', $subscription->id)
            ->where('period_starts_at', $subscription->current_period_starts_at)
            ->whereNotIn('status', ['canceled', 'uncollectible'])
            ->latest('id')->first();
    }

    private function replaceSubscriptionItem(Subscription $subscription, Plan $plan, int $priceMinor, string $currency): void
    {
        $item = $subscription->items()->lockForUpdate()->first();
        $student = StudentProfile::query()->where('user_id', $subscription->subscriber_id)->first();
        $values = ['plan_id' => $plan->id, 'quantity' => 1, 'unit_price_minor' => $priceMinor, 'currency' => strtoupper($currency), 'entitlements' => $this->entitlements($plan, $student)];
        $item ? $item->update($values) : $subscription->items()->create($values);
    }

    private function entitlements(Plan $plan, ?StudentProfile $student = null): array
    {
        $plan->loadMissing('courseSubjectSelections');
        $subjectIds = $plan->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
        $courseId = $student?->course_id ? (int) $student->course_id : ($plan->course_id ? (int) $plan->course_id : null);
        if ($student && $plan->courseSubjectSelections->isNotEmpty() && $courseId) {
            $subjectIds = $plan->courseSubjectSelections->where('course_id', $courseId)->pluck('subject_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        }
        return ['scope' => $plan->scope->value, 'course_id' => $courseId, 'subject_ids' => $subjectIds, 'features' => $plan->features];
    }
}
