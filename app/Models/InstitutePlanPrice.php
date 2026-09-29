<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutePlanPrice extends Model
{
    protected $table = 'institute_plan_prices';
    protected $fillable = ['institute_id', 'plan_id', 'price_minor', 'currency', 'starts_at', 'ends_at', 'is_active', 'metadata'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'metadata' => 'array'];
    }
}
