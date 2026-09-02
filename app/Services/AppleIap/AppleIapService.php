<?php

namespace App\Services\AppleIap;

use App\Models\AppleIapProduct;
use App\Models\AppleIapTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AppleIapService
{
    public function __construct(private AppStoreServerApiClient $apple) {}

    public function products(): array
    {
        return AppleIapProduct::query()->where('is_active', true)->with('plan:id,name,code,billing_interval,billing_interval_count')
            ->orderBy('plan_id')->get()->map(fn (AppleIapProduct $p) => [
                'plan_id' => $p->plan_id,
                'product_id' => $p->product_id,
                'subscription_group_id' => $p->subscription_group_id,
                'plan' => $p->plan,
            ])->values()->all();
    }

    public function verifyAndApply(int $userId, StudentProfile $student, string $transactionId, string $clientProductId): array
    {
        abort_unless(config('payments.apple_iap.enabled', false), 503, 'Apple In-App Purchase is not enabled.');
        abort_unless((int) $student->user_id === $userId, 403);

        if ($known = AppleIapTransaction::query()->where('transaction_id', $transactionId)->first()) {
            abort_unless((int) $known->user_id === $userId, 409, 'This Apple transaction is already linked to another account.');
            return $this->result($known);
        }

        $tx = $this->apple->transaction($transactionId);
        $this->assertTransaction($tx, $clientProductId);
        return $this->applyVerifiedTransaction($userId, $student, $tx);
    }

    public function processNotification(string $signedPayload): void
    {
        $outer = $this->apple->decodeSignedPayload($signedPayload);
        $signedTx = data_get($outer, 'data.signedTransactionInfo');
        if (! is_string($signedTx) || $signedTx === '') return;
        $untrusted = $this->apple->decodeSignedPayload($signedTx);
        $transactionId = (string) ($untrusted['transactionId'] ?? '');
        if ($transactionId === '') return;

        // Never mutate state based only on the incoming JWS body. Re-fetch the transaction
        // from Apple's authenticated App Store Server API first.
        $tx = $this->apple->transaction($transactionId);
        $known = AppleIapTransaction::query()->where('transaction_id', $transactionId)->first();
        $root = $known ?: AppleIapTransaction::query()
            ->where('original_transaction_id', $tx['originalTransactionId'] ?? '__none__')->oldest('id')->first();
        if (! $root) return; // An account must first establish ownership through authenticated app verification/restore.

        $revoked = $this->millis($tx['revocationDate'] ?? null);
        $expires = $this->millis($tx['expiresDate'] ?? null);
        if ($revoked) {
            ($known ?: $root)->update(['revoked_at' => $revoked, 'status' => 'revoked', 'raw_payload' => $tx]);
            if ($root->subscription_id && ($subscription = Subscription::find($root->subscription_id))) {
                $subscription->update(['status' => 'canceled', 'ended_at' => $revoked, 'cancel_at_period_end' => false]);
            }
            return;
        }

        if (! $known) {
            $student = $root->student_profile_id ? StudentProfile::find($root->student_profile_id) : null;
            if ($student) $this->applyVerifiedTransaction((int) $root->user_id, $student, $tx);
            return;
        }

        $known->update(['expires_at' => $expires, 'status' => 'verified', 'raw_payload' => $tx]);
        if ($known->subscription_id && ($subscription = Subscription::find($known->subscription_id))) {
            if ($expires && $expires->isPast()) {
                $subscription->update(['status' => 'expired', 'ended_at' => $expires]);
            } elseif ($expires) {
                $subscription->update(['status' => 'active', 'current_period_ends_at' => $expires, 'grace_ends_at' => null]);
            }
        }
    }

    private function applyVerifiedTransaction(int $userId, StudentProfile $student, array $tx): array
    {
        $mapping = AppleIapProduct::query()->where('product_id', $tx['productId'])->where('is_active', true)->firstOrFail();
        $plan = Plan::query()->where('is_active', true)->findOrFail($mapping->plan_id);
        $purchaseAt = $this->millis($tx['purchaseDate'] ?? null) ?? now();
        $expiresAt = $this->millis($tx['expiresDate'] ?? null) ?? app(SubscriptionService::class)->periodEnd($plan, $purchaseAt);
        $revokedAt = $this->millis($tx['revocationDate'] ?? null);
        abort_if($revokedAt !== null, 422, 'This App Store transaction has been revoked.');
        abort_if($expiresAt->isPast(), 422, 'This App Store subscription has expired.');

        return DB::transaction(function () use ($userId, $student, $tx, $plan, $purchaseAt, $expiresAt) {
            if ($duplicate = AppleIapTransaction::query()->where('transaction_id', $tx['transactionId'])->lockForUpdate()->first()) {
                abort_unless((int) $duplicate->user_id === $userId, 409, 'This Apple transaction is already linked to another account.');
                return $this->result($duplicate);
            }

            $existing = Subscription::query()->where('subscriber_type', 'student')->where('subscriber_id', $userId)
                ->whereIn('status', ['incomplete', 'trialing', 'active', 'past_due', 'paused'])->lockForUpdate()->latest('id')->first();

            if (! $existing) {
                $subscription = app(SubscriptionService::class)->subscribeStudent($student, $plan, $userId, null, 'apple-iap-'.$tx['transactionId']);
                $subscription->update(['current_period_starts_at' => $purchaseAt, 'current_period_ends_at' => $expiresAt]);
                $invoice = app(InvoiceService::class)->createForSubscription($subscription, $purchaseAt, $expiresAt, ['generated_by' => 'apple_iap_initial']);
            } elseif ((int) $existing->plan_id !== (int) $plan->id) {
                $subscription = app(SubscriptionService::class)->applyStoreManagedPlanChange($existing, $plan, $purchaseAt, $expiresAt, [
                    'provider' => 'apple_iap', 'transaction_id' => $tx['transactionId'], 'original_transaction_id' => $tx['originalTransactionId'] ?? null,
                ]);
                $invoice = app(InvoiceService::class)->createForSubscription($subscription, $purchaseAt, $expiresAt, ['generated_by' => 'apple_iap_plan_change']);
            } else {
                $subscription = $existing;
                $subscription->update([
                    'status' => 'active', 'current_period_starts_at' => $purchaseAt, 'current_period_ends_at' => $expiresAt,
                    'grace_ends_at' => null, 'cancel_at_period_end' => false, 'canceled_at' => null,
                ]);
                $invoice = Invoice::query()->where('subscription_id', $subscription->id)->where('period_starts_at', $purchaseAt)->first()
                    ?? app(InvoiceService::class)->createForSubscription($subscription, $purchaseAt, $expiresAt, ['generated_by' => 'apple_iap_renewal']);
            }

            $payment = Payment::query()->where('provider', 'apple_iap')->where('provider_payment_id', $tx['transactionId'])->first();
            if (! $payment) {
                $payment = Payment::create([
                    'invoice_id' => $invoice->id, 'payer_type' => $invoice->billable_type, 'payer_id' => $invoice->billable_id,
                    'provider' => 'apple_iap', 'checkout_reference' => 'APPLE-'.strtoupper(Str::random(16)),
                    'idempotency_key' => 'apple-iap-'.$tx['transactionId'], 'attempt_number' => 1,
                    'provider_payment_id' => $tx['transactionId'], 'status' => 'processing',
                    'amount_minor' => max(0, $invoice->total_minor - $invoice->paid_minor), 'currency' => strtoupper($invoice->currency),
                    'expires_at' => now()->addMinutes(30), 'metadata' => ['apple' => $tx],
                ]);
                if ($payment->amount_minor > 0) {
                    app(PaymentService::class)->complete($payment, $tx['transactionId'], ['apple' => $tx]);
                } else {
                    $invoice->update(['status' => 'paid', 'paid_at' => now()]);
                    $payment->update(['status' => 'succeeded', 'paid_at' => now(), 'receipt_number' => 'APPLE-'.$tx['transactionId'], 'receipt_issued_at' => now()]);
                    $subscription->update(['status' => 'active']);
                }
            }

            $record = AppleIapTransaction::create([
                'user_id' => $userId, 'student_profile_id' => $student->id, 'plan_id' => $plan->id,
                'subscription_id' => $subscription->id, 'payment_id' => $payment->id, 'product_id' => $tx['productId'],
                'transaction_id' => $tx['transactionId'], 'original_transaction_id' => $tx['originalTransactionId'] ?? null,
                'environment' => $tx['environment'] ?? $tx['_api_environment'] ?? null, 'status' => 'verified',
                'purchased_at' => $purchaseAt, 'expires_at' => $expiresAt, 'raw_payload' => $tx,
            ]);
            return $this->result($record);
        });
    }

    private function assertTransaction(array $tx, string $clientProductId): void
    {
        $bundle = (string) config('payments.apple_iap.bundle_id');
        if (($tx['bundleId'] ?? null) !== $bundle) throw new RuntimeException('Apple transaction bundle ID does not match this application.');
        if (($tx['productId'] ?? null) !== $clientProductId) throw new RuntimeException('Apple transaction product ID does not match the requested product.');
        if (($tx['type'] ?? 'Auto-Renewable Subscription') !== 'Auto-Renewable Subscription') throw new RuntimeException('Apple product is not an auto-renewable subscription.');
    }

    private function millis(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') return null;
        return Carbon::createFromTimestampMs((int) $value);
    }

    private function result(AppleIapTransaction $record): array
    {
        return [
            'apple_transaction' => $record->fresh(),
            'subscription' => $record->subscription_id ? Subscription::with(['plan', 'items'])->find($record->subscription_id) : null,
            'payment' => $record->payment_id ? Payment::find($record->payment_id) : null,
        ];
    }
}
