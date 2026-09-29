<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(?int $actorId, string $action, ?Model $model, array $before = [], array $after = [], array $metadata = []): AuditLog
    {
        return AuditLog::create(['actor_user_id' => $actorId, 'action' => $action, 'auditable_type' => $model ? get_class($model) : null, 'auditable_id' => $model?->getKey(), 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent(), 'before' => $before, 'after' => $after, 'metadata' => $metadata]);
    }
}
