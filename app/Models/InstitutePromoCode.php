<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutePromoCode extends Model
{
    protected $fillable = ['institute_id', 'name', 'code', 'discount_type', 'amount_minor', 'percentage', 'starts_at', 'ends_at', 'max_redemptions', 'per_user_limit', 'redemptions_count', 'is_active', 'metadata'];

    protected function casts(): array
    {
        return ['percentage' => 'decimal:4', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'metadata' => 'array'];
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'institute_promo_code_plans');
    }

    public function redemptions()
    {
        return $this->hasMany(PromotionRedemption::class, 'promotion_id')->where('promotion_type', 'institute_promo');
    }
}
