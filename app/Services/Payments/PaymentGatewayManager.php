<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(string $provider): PaymentGateway
    {
        return match ($provider) {
            'jazzcash' => app(JazzCashGateway::class),
            'google_pay' => app(GooglePayGateway::class),
            'mock' => app(MockPaymentGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment provider [{$provider}]."),
        };
    }
}
