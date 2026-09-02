<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GooglePayGateway implements PaymentGateway
{
    public function initiate(Payment $payment, Invoice $invoice): array
    {
        $config = config('payments.google_pay');
        foreach (['merchant_id', 'gateway', 'gateway_merchant_id'] as $key) {
            if (blank($config[$key] ?? null)) throw ValidationException::withMessages(['provider' => "Google Pay {$key} is not configured."]);
        }
        return [
            'type' => 'google_pay',
            'environment' => $config['environment'],
            'merchantInfo' => ['merchantId' => $config['merchant_id'], 'merchantName' => $config['merchant_name']],
            'transactionInfo' => ['totalPriceStatus' => 'FINAL', 'totalPrice' => number_format($payment->amount_minor / 100, 2, '.', ''), 'currencyCode' => strtoupper($payment->currency), 'countryCode' => 'PK'],
            'allowedPaymentMethods' => [[
                'type' => 'CARD',
                'parameters' => ['allowedAuthMethods' => ['PAN_ONLY', 'CRYPTOGRAM_3DS'], 'allowedCardNetworks' => ['VISA', 'MASTERCARD']],
                'tokenizationSpecification' => ['type' => 'PAYMENT_GATEWAY', 'parameters' => ['gateway' => $config['gateway'], 'gatewayMerchantId' => $config['gateway_merchant_id']]],
            ]],
        ];
    }

    public function capture(Payment $payment, string $paymentToken): array
    {
        $url = config('payments.google_pay.processor_url');
        if (blank($url)) throw ValidationException::withMessages(['provider' => 'GOOGLE_PAY_PROCESSOR_URL is not configured.']);
        $request = Http::acceptJson()->withHeader('Idempotency-Key', $payment->checkout_reference);
        if ($secret = config('payments.google_pay.processor_secret')) $request->withToken($secret);
        $response = $request->post($url, ['token' => $paymentToken, 'reference' => $payment->checkout_reference, 'amount_minor' => $payment->amount_minor, 'currency' => $payment->currency]);
        $body = $response->json() ?: [];
        $transactionId = $body['transaction_id'] ?? $body['id'] ?? null;
        return [
            'success' => $response->successful() && ($body['success'] ?? false) === true && filled($transactionId),
            'transaction_id' => $transactionId,
            'code' => $body['code'] ?? (string) $response->status(),
            'message' => $body['message'] ?? ($response->successful() ? 'Captured' : 'Capture failed'),
            'raw' => $body,
        ];
    }

    public function verifyCallback(array $payload): array
    {
        return ['success' => false, 'reference' => null, 'transaction_id' => null, 'code' => 'not_supported', 'message' => 'Google Pay is captured synchronously by the configured processor.', 'raw' => $payload];
    }

    public function refund(Payment $payment, int $amountMinor, string $reason, string $idempotencyKey): array
    {
        $url = config('payments.google_pay.refund_url');
        if (blank($url)) throw ValidationException::withMessages(['provider' => 'GOOGLE_PAY_REFUND_URL is not configured.']);
        $request = Http::acceptJson()->withHeader('Idempotency-Key', $idempotencyKey);
        if ($secret = config('payments.google_pay.processor_secret')) $request->withToken($secret);
        $response = $request->post($url, [
            'transaction_id' => $payment->provider_payment_id,
            'reference' => $payment->checkout_reference,
            'amount_minor' => $amountMinor,
            'currency' => $payment->currency,
            'reason' => $reason,
        ]);
        $body = $response->json() ?: [];
        $refundId = $body['refund_id'] ?? $body['id'] ?? null;
        return [
            'success' => $response->successful() && ($body['success'] ?? false) === true && filled($refundId),
            'refund_id' => $refundId,
            'code' => $body['code'] ?? (string) $response->status(),
            'message' => $body['message'] ?? ($response->successful() ? 'Refund response was incomplete.' : 'Refund failed.'),
            'raw' => $body,
        ];
    }
}
