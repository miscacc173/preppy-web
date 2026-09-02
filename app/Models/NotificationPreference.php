<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';
    protected $fillable = ['user_id', 'notification_type', 'channel', 'is_enabled', 'schedule'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'schedule' => 'array'];
    }
}
