<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmailVerificationChallenge extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = [
        'code_hash',
        'verification_grant_hash',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'verified_at' => 'datetime',
            'verification_grant_expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
