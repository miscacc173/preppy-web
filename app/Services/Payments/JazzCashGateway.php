<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class JazzCashGateway implements PaymentGateway
{
    public function initiate(Payment $payment, Invoice $invoice): array
    {
        $config = config('payments.jazzcash');
        foreach (['merchant_id', 'password', 'integrity_salt', 'return_url', 'endpoint'] as $key) {
            if (blank($config[$key] ?? null)) throw ValidationException::withMessages(['provider' => "JazzCash {$key} is not configured."]);
        }
        if (strtoupper($payment->currency) !== 'PKR') throw ValidationException::withMessages(['provider' => 'JazzCash checkout requires a PKR-priced plan.']);

        $now = now('Asia/Karachi');
        $fields = [
            'pp_Version' => '1.1', 'pp_TxnType' => 'MPAY', 'pp_Language' => 'EN',
            'pp_MerchantID' => $config['merchant_id'], 'pp_SubMerchantID' => '', 'pp_Password' => $config['password'],
            'pp_BankID' => 'TBANK', 'pp_ProductID' => 'RETL', 'pp_TxnRefNo' => $payment->checkout_reference,
            'pp_Amount' => (string) $payment->amount_minor, 'pp_TxnCurrency' => strtoupper($payment->currency),
            'pp_TxnDateTime' => $now->format('YmdHis'), 'pp_BillReference' => $payment->checkout_reference,
            'pp_Description' => 'Preppy subscription '.$invoice->invoice_number,
            'pp_TxnExpiryDateTime' => $now->copy()->addMinutes(30)->format('YmdHis'),
            'pp_ReturnURL' => $config['return_url'], 'pp_SecureHash' => '',
            'ppmpf_1' => (string) $invoice->id, 'ppmpf_2' => (string) $invoice->subscription_id,
            'ppmpf_3' => '', 'ppmpf_4' => '', 'ppmpf_5' => '',
        ];
        $fields['pp_SecureHash'] = $this->hash($fields);

        return ['type' => 'redirect_form', 'method' => 'POST', 'url' => $config['endpoint'], 'fields' => $fields];
    }

    public function capture(Payment $payment, string $paymentToken): array
    {
        throw ValidationException::withMessages(['provider' => 'JazzCash completes payment through its hosted checkout callback.']);
    }

    public function verifyCallback(array $payload): array
    {
        $provided = (string) ($payload['pp_SecureHash'] ?? '');
        $valid = filled($provided) && hash_equals(strtolower($this->hash($payload)), strtolower($provided));
        $success = $valid && (string) ($payload['pp_ResponseCode'] ?? '') === '000';
        return [
            'success' => $success,
            'reference' => $payload['pp_BillReference'] ?? $payload['pp_TxnRefNo'] ?? null,
            'transaction_id' => $payload['pp_RetreivalReferenceNo'] ?? $payload['pp_TxnRefNo'] ?? null,
            'code' => $valid ? (string) ($payload['pp_ResponseCode'] ?? '') : 'invalid_signature',
            'message' => $valid ? (string) ($payload['pp_ResponseMessage'] ?? '') : 'JazzCash signature verification failed.',
            'raw' => $payload,
        ];
    }

    public function refund(Payment $payment, int $amountMinor, string $reason, string $idempotencyKey): array
    {
        $endpoint = config('payments.jazzcash.refund_endpoint');
        if (blank($endpoint)) throw ValidationException::withMessages(['provider' => 'JAZZCASH_REFUND_ENDPOINT is not configured.']);
        abort_if(blank($payment->provider_payment_id), 422, 'The JazzCash transaction reference is missing.');
        $fields = [
            'pp_Version' => '1.1',
            'pp_TxnType' => 'REFUND',
            'pp_MerchantID' => config('payments.jazzcash.merchant_id'),
            'pp_Password' => config('payments.jazzcash.password'),
            'pp_TxnRefNo' => 'RF'.strtoupper(substr(hash('sha256', $idempotencyKey), 0, 20)),
            'pp_OriginalTransactionRef' => $payment->provider_payment_id,
            'pp_Amount' => (string) $amountMinor,
            'pp_TxnCurrency' => strtoupper($payment->currency),
            'pp_Description' => mb_substr($reason, 0, 200),
        ];
        $fields['pp_SecureHash'] = $this->hash($fields);
        $response = Http::asForm()->acceptJson()->withHeader('Idempotency-Key', $idempotencyKey)->post($endpoint, $fields);
        $body = $response->json() ?: [];
        $signature = (string) ($body['pp_SecureHash'] ?? '');
        $signatureValid = filled($signature) && hash_equals(strtolower($this->hash($body)), strtolower($signature));
        $refundId = $body['pp_RetreivalReferenceNo'] ?? $body['pp_TxnRefNo'] ?? null;
        return [
            'success' => $response->successful() && $signatureValid && (string) ($body['pp_ResponseCode'] ?? '') === '000' && filled($refundId),
            'refund_id' => $refundId,
            'code' => $signatureValid ? (string) ($body['pp_ResponseCode'] ?? $response->status()) : 'invalid_signature',
            'message' => $signatureValid ? (string) ($body['pp_ResponseMessage'] ?? 'Refund response was incomplete.') : 'JazzCash refund signature verification failed.',
            'raw' => $body,
        ];
    }

    private function hash(array $fields): string
    {
        unset($fields['pp_SecureHash']);
        $fields = array_filter($fields, fn ($key) => str_starts_with((string) $key, 'pp_') || str_starts_with((string) $key, 'ppmpf_'), ARRAY_FILTER_USE_KEY);
        ksort($fields);
        $values = array_values(array_filter($fields, fn ($value) => $value !== null && $value !== ''));
        $salt = (string) config('payments.jazzcash.integrity_salt');
        return hash_hmac('sha256', $salt.'&'.implode('&', $values), $salt);
    }
}
