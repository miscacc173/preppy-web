<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRefund extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime', 'metadata' => 'array'];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
