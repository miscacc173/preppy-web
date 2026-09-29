<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRedemption extends Model
{
    protected $fillable = ['promotion_type', 'promotion_id', 'code', 'user_id', 'subscription_id', 'invoice_id', 'payment_id', 'discount_minor', 'status', 'expires_at', 'consumed_at', 'metadata'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime', 'metadata' => 'array'];
    }
}
