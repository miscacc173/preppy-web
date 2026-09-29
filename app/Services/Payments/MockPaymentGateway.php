<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

class MockPaymentGateway implements PaymentGateway
{
    public function initiate(Payment $payment, Invoice $invoice): array
    {
        $this->ensureEnabled();

        return [
            'type' => 'mock_card',
            'provider' => 'mock',
            'demo_card' => [
                'number' => '4242 4242 4242 4242',
                'expiry' => '12/30',
                'cvv' => '123',
            ],
            'amount_minor' => $payment->amount_minor,
            'currency' => strtoupper($payment->currency),
        ];
    }

    public function capture(Payment $payment, string $paymentToken): array
    {
        $this->ensureEnabled();

        if ($paymentToken === 'mock_success') {
            return [
                'success' => true,
                'transaction_id' => 'MOCK-'.strtoupper(substr(hash('sha256', $payment->checkout_reference), 0, 20)),
                'code' => null,
                'message' => 'Mock payment approved.',
                'raw' => ['provider' => 'mock', 'result' => 'approved'],
            ];
        }

        return [
            'success' => false,
            'transaction_id' => null,
            'code' => 'mock_declined',
            'message' => 'Mock payment was declined.',
            'raw' => ['provider' => 'mock', 'result' => 'declined'],
        ];
    }

    public function verifyCallback(array $payload): array
    {
        return [
            'success' => false,
            'reference' => null,
            'transaction_id' => null,
            'code' => 'not_supported',
            'message' => 'Mock payments are captured synchronously.',
            'raw' => $payload,
        ];
    }

    public function refund(Payment $payment, int $amountMinor, string $reason, string $idempotencyKey): array
    {
        $this->ensureEnabled();

        return [
            'success' => true,
            'refund_id' => 'MOCK-RF-'.strtoupper(substr(hash('sha256', $idempotencyKey), 0, 18)),
            'code' => null,
            'message' => 'Mock refund approved.',
            'raw' => [
                'provider' => 'mock',
                'amount_minor' => $amountMinor,
                'reason' => $reason,
            ],
        ];
    }

    private function ensureEnabled(): void
    {
        if (! config('payments.mock.enabled', false) || ! app()->environment(['local', 'development', 'testing'])) {
            throw ValidationException::withMessages(['provider' => 'Mock payments are not enabled in this environment.']);
        }
    }
}
