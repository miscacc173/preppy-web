<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GuestDemoSession extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'started_at' => 'datetime', 'expires_at' => 'datetime', 'converted_at' => 'datetime', 'progress_snapshot' => 'array', 'metadata' => 'array'];
    }
}