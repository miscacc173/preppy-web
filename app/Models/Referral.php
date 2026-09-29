<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = ['referrer_user_id', 'referred_user_id', 'referral_code_id', 'subscription_id', 'invoice_id', 'qualifying_payment_id', 'reward_discount_id', 'status', 'qualified_at', 'rewarded_at', 'metadata'];
    protected function casts(): array { return ['qualified_at' => 'datetime', 'rewarded_at' => 'datetime', 'metadata' => 'array']; }
}
