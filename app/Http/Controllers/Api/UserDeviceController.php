<?php

namespace App\Http\Controllers\Api;

use App\Models\UserDevice;

class UserDeviceController extends CrudController
{
    protected string $model = UserDevice::class;
    protected array $searchable = [];
    protected array $storeRules = ['user_id' => 'required|exists:users,id', 'platform' => 'required|in:web,ios,android', 'device_id' => 'required|string|max:190', 'push_token' => 'nullable|string', 'timezone' => 'nullable|string|max:64', 'last_seen_at' => 'nullable|date', 'is_active' => 'boolean', 'metadata' => 'array'];
    protected array $updateRules = ['user_id' => 'required|exists:users,id', 'platform' => 'required|in:web,ios,android', 'device_id' => 'required|string|max:190', 'push_token' => 'nullable|string', 'timezone' => 'nullable|string|max:64', 'last_seen_at' => 'nullable|date', 'is_active' => 'boolean', 'metadata' => 'array'];
}
