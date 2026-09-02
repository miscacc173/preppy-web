<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionPayout extends Model
{
    protected $table = 'commission_payouts';
    protected $fillable = ['institute_id', 'reference', 'status', 'amount_minor', 'currency', 'period_starts_at', 'period_ends_at', 'paid_at', 'metadata'];

    protected function casts(): array
    {
        return ['period_starts_at' => 'datetime', 'period_ends_at' => 'datetime', 'paid_at' => 'datetime', 'metadata' => 'array'];
    }
}
