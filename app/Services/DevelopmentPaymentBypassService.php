<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class DevelopmentPaymentBypassService
{
    public function enabled(): bool
    {
        return (bool) config('payments.development_bypass', false)
            && app()->environment(['local', 'development', 'testing']);
    }

    public function activate(Subscription $subscription, Invoice $invoice, int $actorId, string $idempotencyKey): array
    {
        abort_unless($this->enabled(), 403, 'Development payment bypass is not enabled.');
        abort_unless($invoice->subscription_id === $subscription->id, 422, 'The invoice does not belong to this subscription.');

        return DB::transaction(function () use ($subscription, $invoice, $actorId, $idempotencyKey): array {
            $lockedSubscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $subscriptionMetadata = $lockedSubscription->metadata ?? [];
            $existingBypass = is_array($subscriptionMetadata['development_payment_bypass'] ?? null)
                ? $subscriptionMetadata['development_payment_bypass']
                : [];
            $subscriptionMetadata['development_payment_bypass'] = [
                'activated_at' => $existingBypass['activated_at'] ?? now()->toIso8601String(),
                'activated_by' => $actorId,
                'idempotency_key' => $idempotencyKey,
            ];
            $lockedSubscription->update(['status' => 'active', 'grace_ends_at' => null, 'metadata' => $subscriptionMetadata]);

            $invoiceMetadata = $lockedInvoice->metadata ?? [];
            $invoiceMetadata['development_payment_bypass'] = true;
            $invoiceMetadata['bypass_actor_id'] = $actorId;
            $invoiceMetadata['bypass_idempotency_key'] = $idempotencyKey;
            $lockedInvoice->update([
                'status' => 'waived',
                'paid_minor' => 0,
                'paid_at' => now(),
                'next_retry_at' => null,
                'collection_ends_at' => null,
                'metadata' => $invoiceMetadata,
            ]);

            if (data_get($lockedInvoice->metadata, 'billing_event') === 'plan_upgrade') {
                $lockedSubscription = app(SubscriptionService::class)->finalizeUpgradeFromInvoice($lockedSubscription, $lockedInvoice);
            }

            return [
                'subscription' => $lockedSubscription->fresh()->load(['plan', 'items']),
                'invoice' => $lockedInvoice->fresh()->load('items'),
                'payment' => null,
                'gateway' => ['type' => 'development_bypass'],
                'requires_payment' => false,
            ];
        });
    }
}
