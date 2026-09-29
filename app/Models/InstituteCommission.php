<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteCommission extends Model
{
    protected $guarded = [];
    protected $appends = ['settlement_type', 'net_amount_minor'];

    protected function casts(): array
    {
        return ['earned_at' => 'datetime', 'available_at' => 'datetime', 'paid_at' => 'datetime', 'settled_at' => 'datetime', 'metadata' => 'array'];
    }

    public function getSettlementTypeAttribute(): string
    {
        return $this->direction === 'platform_collects_from_institute' ? 'platform_fee' : 'institute_earning';
    }

    public function getNetAmountMinorAttribute(): int
    {
        return max(0, (int) $this->commission_amount_minor - (int) $this->reversed_amount_minor);
    }

    public function adjustments()
    {
        return $this->hasMany(InstituteCommissionAdjustment::class);
    }
}
