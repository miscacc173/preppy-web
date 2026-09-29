<?php

namespace App\Http\Controllers\Api;

use App\Models\NotificationPreference;

class NotificationPreferenceController extends CrudController
{
    protected string $model = NotificationPreference::class;
    protected array $searchable = [];
    protected array $storeRules = ['user_id' => 'required|exists:users,id', 'notification_type' => 'required|string|max:80', 'channel' => 'required|in:email,push,sms,in_app', 'is_enabled' => 'boolean', 'schedule' => 'array'];
    protected array $updateRules = ['user_id' => 'required|exists:users,id', 'notification_type' => 'required|string|max:80', 'channel' => 'required|in:email,push,sms,in_app', 'is_enabled' => 'boolean', 'schedule' => 'array'];
}
