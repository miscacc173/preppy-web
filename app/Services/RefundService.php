<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(private PaymentGatewayManager $gateways) {}

    public function refund(Payment $payment, int $amountMinor, string $reason, User $actor, string $idempotencyKey, bool $revokeEntitlement = false): PaymentRefund
    {
        $existing = PaymentRefund::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            abort_unless($existing->payment_id === $payment->id && $existing->amount_minor === $amountMinor, 409, 'The idempotency key was already used for another refund.');
            return $existing;
        }
        abort_unless(in_array($payment->status, ['succeeded', 'partially_refunded'], true), 422, 'Only successful payments can be refunded.');
        $remaining = (int) $payment->amount_minor - (int) $payment->refunded_minor;
        abort_if($amountMinor < 1 || $amountMinor > $remaining, 422, 'Refund amount exceeds the refundable payment balance.');
        $refund = PaymentRefund::create([
            'payment_id' => $payment->id, 'invoice_id' => $payment->invoice_id, 'idempotency_key' => $idempotencyKey,
            'status' => 'processing', 'amount_minor' => $amountMinor, 'currency' => $payment->currency,
            'reason' => $reason, 'initiated_by' => $actor->id, 'metadata' => ['revoke_entitlement' => $revokeEntitlement],
        ]);
        try {
            $result = $this->gateways->driver((string) $payment->provider)->refund($payment, $amountMinor, $reason, $idempotencyKey);
            if (! $result['success']) {
                $refund->update(['status' => 'failed', 'failure_code' => $result['code'], 'failure_message' => $result['message'], 'processed_at' => now(), 'metadata' => [...($refund->metadata ?? []), 'gateway_response' => $result['raw']]]);
                throw ValidationException::withMessages(['refund' => $result['message'] ?: 'The provider rejected the refund.']);
            }
            return $this->complete($refund, $result['refund_id'], $result['raw']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $refund->update(['status' => 'failed', 'failure_code' => 'provider_error', 'failure_message' => $exception->getMessage(), 'processed_at' => now()]);
            throw $exception;
        }
    }

    public function complete(PaymentRefund $refund, ?string $providerRefundId, array $raw = []): PaymentRefund
    {
        return DB::transaction(function () use ($refund, $providerRefundId, $raw): PaymentRefund {
            $locked = PaymentRefund::query()->lockForUpdate()->findOrFail($refund->id);
            if ($locked->status === 'succeeded') return $locked;
            $payment = Payment::query()->lockForUpdate()->findOrFail($locked->payment_id);
            $refunded = (int) $payment->refunded_minor + (int) $locked->amount_minor;
            abort_if($refunded > $payment->amount_minor, 422, 'Refund exceeds the payment amount.');
            $fullyRefunded = $refunded >= $payment->amount_minor;
            $payment->update(['refunded_minor' => $refunded, 'status' => $fullyRefunded ? 'refunded' : 'partially_refunded']);
            $locked->update(['status' => 'succeeded', 'provider_refund_id' => $providerRefundId, 'processed_at' => now(), 'failure_code' => null, 'failure_message' => null, 'metadata' => [...($locked->metadata ?? []), 'gateway_response' => $raw]]);
            $invoice = $payment->invoice()->lockForUpdate()->first();
            if ($invoice) {
                // Preserve gross collections so a refund never re-opens the invoice for payment.
                $invoiceRefunded = min((int) $invoice->paid_minor, (int) $invoice->refunded_minor + (int) $locked->amount_minor);
                $netPaid = max(0, (int) $invoice->paid_minor - $invoiceRefunded);
                $invoice->update(['refunded_minor' => $invoiceRefunded, 'status' => $netPaid === 0 ? 'refunded' : 'partially_refunded']);
                if (($locked->metadata['revoke_entitlement'] ?? false) && $invoice->subscription_id) {
                    $subscription = Subscription::find($invoice->subscription_id);
                    $subscription?->update(['status' => $netPaid === 0 ? 'canceled' : 'past_due', 'ended_at' => $netPaid === 0 ? now() : null, 'grace_ends_at' => $netPaid === 0 ? null : now()->addDays(config('billing.grace_days', 7))]);
                }
            }
            app(CommissionService::class)->reverseForRefund($payment, $locked);
            return $locked->fresh(['payment', 'invoice']);
        });
    }
}
