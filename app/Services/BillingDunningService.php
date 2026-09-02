<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;

class BillingDunningService
{
    /** @return array{expired_payments:int,retries:int,canceled:int} */
    public function process(): array
    {
        $expired = Payment::query()->whereIn('status', ['pending', 'processing'])->where('expires_at', '<=', now())
            ->update(['status' => 'failed', 'failure_code' => 'attempt_expired', 'failure_message' => 'The payment attempt expired before provider confirmation.']);
        $retries = 0; $canceled = 0;
        $intervals = config('billing.retry_intervals_hours', [24, 72, 120]);
        Invoice::query()->whereIn('status', ['issued', 'partially_paid'])->where('due_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now()))
            ->with('subscription')->orderBy('id')->chunkById(100, function ($invoices) use (&$retries, &$canceled, $intervals): void {
                foreach ($invoices as $invoice) {
                    $subscription = $invoice->subscription;
                    $isUpgradeInvoice = data_get($invoice->metadata, 'billing_event') === 'plan_upgrade';
                    $collectionEnd = $invoice->collection_ends_at ?? $invoice->due_at->copy()->addDays(config('billing.collection_days', 14));
                    if ($collectionEnd->isPast() || $invoice->retry_count >= count($intervals)) {
                        $invoice->update(['status' => 'uncollectible', 'collection_ends_at' => $collectionEnd, 'next_retry_at' => null, 'canceled_at' => now()]);
                        if (! $isUpgradeInvoice && $subscription && in_array($subscription->status->value, ['incomplete', 'past_due'], true)) {
                            $subscription->update(['status' => 'canceled', 'ended_at' => now(), 'grace_ends_at' => null]);
                        }
                        $canceled++;
                        continue;
                    }
                    $retryCount = (int) $invoice->retry_count + 1;
                    $nextHours = $intervals[$retryCount - 1] ?? end($intervals) ?: 24;
                    $invoice->update(['retry_count' => $retryCount, 'collection_ends_at' => $collectionEnd, 'next_retry_at' => now()->addHours($nextHours)]);
                    if (! $isUpgradeInvoice && $subscription && in_array($subscription->status->value, ['active', 'trialing', 'past_due'], true)) {
                        $subscription->update(['status' => 'past_due', 'grace_ends_at' => $subscription->grace_ends_at ?? now()->addDays(config('billing.grace_days', 7))]);
                    }
                    $retries++;
                }
            });
        return ['expired_payments' => $expired, 'retries' => $retries, 'canceled' => $canceled];
    }
}
