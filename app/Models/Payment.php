<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $fillable = ['invoice_id', 'payer_type', 'payer_id', 'provider', 'checkout_reference', 'idempotency_key', 'retry_of_payment_id', 'attempt_number', 'provider_payment_id', 'status', 'failure_code', 'failure_message', 'amount_minor', 'refunded_minor', 'currency', 'receipt_number', 'receipt_issued_at', 'expires_at', 'paid_at', 'metadata'];

    protected function casts(): array
    {
        return ['receipt_issued_at' => 'datetime', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'metadata' => 'array'];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function refunds()
    {
        return $this->hasMany(PaymentRefund::class);
    }
}
