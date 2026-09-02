<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppleIapTransaction extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return ['purchased_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime', 'raw_payload' => 'array'];
    }
}
