<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    /** @return array{payment: Payment, gateway: array<string, mixed>} */
    public function initiate(Invoice $invoice, string $provider, string $idempotencyKey): array
    {
        $idempotencyKey = trim($idempotencyKey);
        abort_if($idempotencyKey === '' || strlen($idempotencyKey) > 100, 422, 'A valid idempotency key is required.');
        $known = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($known) abort_unless($known->invoice_id === $invoice->id && $known->provider === $provider, 409, 'The idempotency key was already used for another checkout.');
        if ($known && in_array($known->status, ['succeeded', 'failed', 'partially_refunded', 'refunded'], true)) {
            return ['payment' => $known, 'gateway' => ['type' => 'completed', 'status' => $known->status]];
        }
        abort_if(! $known && $invoice->paid_minor >= $invoice->total_minor, 422, 'Invoice is already paid.');
        $payment = DB::transaction(function () use ($invoice, $provider, $idempotencyKey): Payment {
            $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                abort_unless($existing->invoice_id === $invoice->id && $existing->provider === $provider, 409, 'The idempotency key was already used for another checkout.');
                return $existing;
            }
            $active = Payment::query()->where('invoice_id', $invoice->id)->where('provider', $provider)
                ->whereIn('status', ['pending', 'processing'])->where('expires_at', '>', now())->latest('id')->lockForUpdate()->first();
            if ($active) return $active;
            $previous = Payment::query()->where('invoice_id', $invoice->id)->latest('id')->lockForUpdate()->first();
            return Payment::create([
                'invoice_id' => $invoice->id, 'payer_type' => $invoice->billable_type, 'payer_id' => $invoice->billable_id,
                'provider' => $provider, 'checkout_reference' => 'CHK-'.strtoupper(Str::random(18)),
                'idempotency_key' => $idempotencyKey, 'retry_of_payment_id' => $previous?->id,
                'attempt_number' => ((int) $previous?->attempt_number) + 1,
                'status' => 'pending', 'amount_minor' => $invoice->total_minor - $invoice->paid_minor,
                'currency' => strtoupper($invoice->currency),
                'expires_at' => now()->addMinutes(config('billing.payment_attempt_expires_minutes', 30)), 'metadata' => [],
            ]);
        });
        if (in_array($payment->status, ['succeeded', 'failed', 'partially_refunded', 'refunded'], true)) {
            return ['payment' => $payment, 'gateway' => ['type' => 'completed', 'status' => $payment->status]];
        }
        try {
            $gateway = $this->gateways->driver($provider)->initiate($payment, $invoice);
            $payment->update(['status' => 'processing', 'metadata' => [...($payment->metadata ?? []), 'gateway_type' => $gateway['type'] ?? null]]);
            return ['payment' => $payment->fresh(), 'gateway' => $gateway];
        } catch (\Throwable $exception) {
            $payment->update(['status' => 'failed', 'failure_code' => 'initiation_failed', 'failure_message' => $exception->getMessage()]);
            throw $exception;
        }
    }

    public function captureGooglePay(Payment $payment, string $token): Payment
    {
        abort_unless($payment->provider === 'google_pay', 422, 'This payment is not a Google Pay checkout.');
        if ($payment->status === 'succeeded') return $payment;
        abort_unless(in_array($payment->status, ['pending', 'processing'], true), 422, 'This payment cannot be captured.');
        $result = $this->gateways->driver('google_pay')->capture($payment, $token);
        return $result['success']
            ? $this->complete($payment, $result['transaction_id'], $result['raw'])
            : $this->fail($payment, $result['code'], $result['message'], $result['raw']);
    }

    public function captureMock(Payment $payment, string $token): Payment
    {
        abort_unless($payment->provider === 'mock', 422, 'This payment is not a mock checkout.');
        if ($payment->status === 'succeeded') return $payment;
        abort_unless(in_array($payment->status, ['pending', 'processing'], true), 422, 'This payment cannot be captured.');
        $result = $this->gateways->driver('mock')->capture($payment, $token);
        return $result['success']
            ? $this->complete($payment, $result['transaction_id'], $result['raw'])
            : $this->fail($payment, $result['code'], $result['message'], $result['raw']);
    }

    public function handleCallback(string $provider, array $payload): Payment
    {
        $result = $this->gateways->driver($provider)->verifyCallback($payload);
        $payment = Payment::query()->where('provider', $provider)->where('checkout_reference', $result['reference'])->firstOrFail();
        return $result['success']
            ? $this->complete($payment, $result['transaction_id'], $result['raw'])
            : $this->fail($payment, $result['code'], $result['message'], $result['raw']);
    }

    public function complete(Payment $payment, ?string $transactionId, array $raw = []): Payment
    {
        return DB::transaction(function () use ($payment, $transactionId, $raw): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if (in_array($locked->status, ['succeeded', 'partially_refunded', 'refunded'], true)) return $locked;
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($locked->invoice_id);
            abort_if($locked->amount_minor > $invoice->total_minor - $invoice->paid_minor, 422, 'Payment exceeds the outstanding balance.');
            $locked->update(['status' => 'succeeded', 'provider_payment_id' => $transactionId ?: $locked->provider_payment_id, 'paid_at' => now(), 'receipt_number' => $locked->receipt_number ?: 'RCT-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)), 'receipt_issued_at' => $locked->receipt_issued_at ?: now(), 'failure_code' => null, 'failure_message' => null, 'metadata' => [...($locked->metadata ?? []), 'gateway_response' => $raw]]);
            $paid = $invoice->paid_minor + $locked->amount_minor;
            $fullyPaid = $paid >= $invoice->total_minor;
            $invoice->update(['paid_minor' => $paid, 'status' => $fullyPaid ? 'paid' : 'partially_paid', 'paid_at' => $fullyPaid ? now() : null, 'next_retry_at' => null]);
            if ($fullyPaid && $invoice->subscription_id) {
                $subscription = Subscription::findOrFail($invoice->subscription_id);
                if (data_get($invoice->metadata, 'billing_event') === 'plan_upgrade') {
                    $subscription = app(SubscriptionService::class)->finalizeUpgradeFromInvoice($subscription, $invoice);
                } else {
                    $subscription->update(['status' => 'active', 'grace_ends_at' => null]);
                }
                app(PromotionService::class)->consumeForPayment($invoice, $locked->id);
                app(ReferralService::class)->rewardForPayment($locked->fresh('invoice'));
                app(CommissionService::class)->accrue($subscription, $locked->id, $locked->amount_minor);
            }
            return $locked->fresh();
        });
    }

    public function fail(Payment $payment, ?string $code, ?string $message, array $raw = []): Payment
    {
        $payment->refresh();
        if (in_array($payment->status, ['succeeded', 'partially_refunded', 'refunded'], true)) return $payment;
        $payment->update(['status' => 'failed', 'failure_code' => $code, 'failure_message' => $message, 'metadata' => [...($payment->metadata ?? []), 'gateway_response' => $raw]]);
        $invoice = $payment->invoice_id ? Invoice::find($payment->invoice_id) : null;
        if ($invoice) {
            $intervals = config('billing.retry_intervals_hours', [24, 72, 120]);
            $nextHours = $intervals[min((int) $invoice->retry_count, max(0, count($intervals) - 1))] ?? 24;
            $invoice->update(['next_retry_at' => now()->addHours($nextHours), 'collection_ends_at' => $invoice->collection_ends_at ?? now()->addDays(config('billing.collection_days', 14))]);
        }
        if ($invoice?->subscription_id && data_get($invoice->metadata, 'billing_event') !== 'plan_upgrade') {
            $subscription = Subscription::find($invoice->subscription_id);
            if ($subscription && in_array($subscription->status->value, ['active', 'trialing', 'past_due'], true)) {
                $subscription->update(['status' => 'past_due', 'grace_ends_at' => $subscription->grace_ends_at ?? now()->addDays(config('billing.grace_days', 7))]);
            }
        }
        throw ValidationException::withMessages(['payment' => $message ?: 'Payment failed.']);
    }
}
