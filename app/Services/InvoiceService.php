<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function createForSubscription(Subscription $subscription, ?CarbonInterface $periodStart = null, ?CarbonInterface $periodEnd = null, array $adjustments = []): Invoice
    {
        $subscription->loadMissing('items');
        $start = $periodStart ?? $subscription->current_period_starts_at;
        $end = $periodEnd ?? $subscription->current_period_ends_at;

        return DB::transaction(function () use ($subscription, $start, $end, $adjustments): Invoice {
            $existing = Invoice::query()->where('subscription_id', $subscription->id)->where('period_starts_at', $start)->first();
            if ($existing) return $existing->load('items');
            $subtotal = (int) $subscription->items->sum(fn ($item) => $item->unit_price_minor * $item->quantity);
            $discount = min($subtotal, (int) ($adjustments['discount_minor'] ?? 0));
            $tax = (int) ($adjustments['tax_minor'] ?? 0);
            $dueAt = $adjustments['due_at'] ?? now()->addDays(3);
            if (! $dueAt instanceof CarbonInterface) $dueAt = Carbon::parse($dueAt);
            $invoice = Invoice::create([
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'subscription_id' => $subscription->id, 'billable_type' => $subscription->subscriber_type,
                'billable_id' => $subscription->subscriber_id, 'status' => 'issued',
                'subtotal_minor' => $subtotal, 'discount_minor' => $discount, 'tax_minor' => $tax,
                'total_minor' => max(0, $subtotal - $discount + $tax), 'paid_minor' => 0,
                'currency' => $subscription->items->first()?->currency ?? 'PKR',
                'period_starts_at' => $start, 'period_ends_at' => $end,
                'issued_at' => now(), 'due_at' => $dueAt, 'next_retry_at' => $dueAt,
                'collection_ends_at' => $dueAt->copy()->addDays(config('billing.collection_days', 14)),
                'metadata' => ['generated_by' => $adjustments['generated_by'] ?? 'subscription'],
            ]);
            foreach ($subscription->items as $item) {
                $invoice->items()->create(['description' => 'Subscription: '.($subscription->plan?->name ?? 'Plan #'.$item->plan_id), 'quantity' => $item->quantity, 'unit_price_minor' => $item->unit_price_minor, 'line_total_minor' => $item->unit_price_minor * $item->quantity]);
            }
            return $invoice->load('items');
        });
    }
    /** @param array<string,mixed> $quote */
    public function createForPlanUpgrade(Subscription $subscription, \App\Models\Plan $targetPlan, array $quote, int $promotionDiscountMinor = 0): Invoice
    {
        $start = now();
        $end = app(SubscriptionService::class)->periodEnd($targetPlan, $start);
        $targetPrice = (int) $quote['target_price_minor'];
        $credit = max(0, min($targetPrice, (int) ($quote['unused_credit_minor'] ?? 0)));
        $afterCredit = max(0, $targetPrice - $credit);
        $promoDiscount = max(0, min($afterCredit, $promotionDiscountMinor));
        $totalDiscount = min($targetPrice, $credit + $promoDiscount);

        return DB::transaction(function () use ($subscription, $targetPlan, $quote, $start, $end, $targetPrice, $credit, $promoDiscount, $totalDiscount): Invoice {
            $invoice = Invoice::create([
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'subscription_id' => $subscription->id,
                'billable_type' => $subscription->subscriber_type,
                'billable_id' => $subscription->subscriber_id,
                'status' => 'issued',
                'subtotal_minor' => $targetPrice,
                'discount_minor' => $totalDiscount,
                'tax_minor' => 0,
                'total_minor' => max(0, $targetPrice - $totalDiscount),
                'paid_minor' => 0,
                'currency' => $quote['currency'],
                'period_starts_at' => $start,
                'period_ends_at' => $end,
                'issued_at' => now(),
                'due_at' => now()->addDays(3),
                'next_retry_at' => now()->addDays(3),
                'collection_ends_at' => now()->addDays(3 + config('billing.collection_days', 14)),
                'metadata' => [
                    'generated_by' => 'plan_upgrade',
                    'billing_event' => 'plan_upgrade',
                    'previous_plan_id' => $quote['current_plan_id'],
                    'target_plan_id' => $targetPlan->id,
                    'target_price_minor' => $targetPrice,
                    'source_invoice_id' => $quote['source_invoice_id'] ?? null,
                    'carry_forward_credit_minor' => $credit,
                    'promotion_discount_minor' => $promoDiscount,
                    'credit_base_minor' => $quote['credit_base_minor'] ?? null,
                    'remaining_seconds' => $quote['remaining_seconds'] ?? null,
                    'cycle_seconds' => $quote['cycle_seconds'] ?? null,
                ],
            ]);
            $invoice->items()->create([
                'description' => 'Plan upgrade: '.$targetPlan->name,
                'quantity' => 1,
                'unit_price_minor' => $targetPrice,
                'line_total_minor' => $targetPrice,
                'metadata' => ['type' => 'plan_upgrade', 'plan_id' => $targetPlan->id],
            ]);
            if ($credit > 0) {
                $invoice->items()->create([
                    'description' => 'Credit carried forward from unused previous plan value',
                    'quantity' => 1,
                    'unit_price_minor' => 0,
                    'line_total_minor' => 0,
                    'metadata' => ['type' => 'carry_forward_credit', 'amount_minor' => $credit, 'source_invoice_id' => $quote['source_invoice_id'] ?? null],
                ]);
            }
            if ($promoDiscount > 0) {
                $invoice->items()->create([
                    'description' => 'Promo / discount code',
                    'quantity' => 1,
                    'unit_price_minor' => 0,
                    'line_total_minor' => 0,
                    'metadata' => ['type' => 'promotion_discount', 'amount_minor' => $promoDiscount],
                ]);
            }
            return $invoice->load('items');
        });
    }

}
