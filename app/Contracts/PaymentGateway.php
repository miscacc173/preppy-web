<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\Payment;

interface PaymentGateway
{
    /** @return array<string, mixed> */
    public function initiate(Payment $payment, Invoice $invoice): array;

    /** @return array{success: bool, transaction_id: ?string, code: ?string, message: ?string, raw: array<string, mixed>} */
    public function capture(Payment $payment, string $paymentToken): array;

    /** @return array{success: bool, reference: ?string, transaction_id: ?string, code: ?string, message: ?string, raw: array<string, mixed>} */
    public function verifyCallback(array $payload): array;

    /** @return array{success: bool, refund_id: ?string, code: ?string, message: ?string, raw: array<string, mixed>} */
    public function refund(Payment $payment, int $amountMinor, string $reason, string $idempotencyKey): array;
}
