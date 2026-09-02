<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['invoice_number', 'subscription_id', 'billable_type', 'billable_id', 'status', 'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'paid_minor', 'refunded_minor', 'retry_count', 'currency', 'period_starts_at', 'period_ends_at', 'issued_at', 'due_at', 'next_retry_at', 'collection_ends_at', 'paid_at', 'canceled_at', 'metadata'];

    protected function casts(): array
    {
        return ['period_starts_at' => 'datetime', 'period_ends_at' => 'datetime', 'issued_at' => 'datetime', 'due_at' => 'datetime', 'next_retry_at' => 'datetime', 'collection_ends_at' => 'datetime', 'paid_at' => 'datetime', 'canceled_at' => 'datetime', 'metadata' => 'array'];
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
