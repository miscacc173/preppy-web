<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Model;

class InstituteCommissionRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['commission_type' => CommissionType::class, 'percentage' => 'decimal:4', 'metadata' => 'array', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }
}