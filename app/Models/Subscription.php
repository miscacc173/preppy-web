<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => SubscriptionStatus::class, 'trial_ends_at' => 'datetime', 'grace_ends_at' => 'datetime', 'current_period_starts_at' => 'datetime', 'current_period_ends_at' => 'datetime', 'cancel_at_period_end' => 'boolean', 'canceled_at' => 'datetime', 'ended_at' => 'datetime', 'metadata' => 'array'];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function items()
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
