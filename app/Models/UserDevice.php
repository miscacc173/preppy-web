<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $table = 'user_devices';
    protected $fillable = ['user_id', 'platform', 'device_id', 'push_token', 'timezone', 'last_seen_at', 'is_active', 'metadata'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'is_active' => 'boolean', 'metadata' => 'array'];
    }
}
