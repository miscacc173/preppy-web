<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discounts';
    protected $fillable = ['name', 'code', 'discount_type', 'amount_minor', 'percentage', 'institute_id', 'plan_id', 'starts_at', 'ends_at', 'max_redemptions', 'redemptions_count', 'is_active', 'rules'];

    protected function casts(): array
    {
        return ['percentage' => 'decimal:4', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'rules' => 'array'];
    }
}
