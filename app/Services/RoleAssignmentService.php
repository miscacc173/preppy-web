<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleAssignmentService
{
    public function assign(User $user, string $roleSlug, string $contextType = 'system', ?int $contextId = null, ?int $assignedBy = null): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        if ($role->scope === 'institute' && (!$contextId || $contextType !== 'institute')) throw ValidationException::withMessages(['role' => 'Institute roles require institute context.']);
        DB::table('user_roles')->updateOrInsert(['user_id' => $user->id, 'role_id' => $role->id, 'context_type' => $contextType, 'context_id' => $contextId], ['assigned_by' => $assignedBy, 'updated_at' => now(), 'created_at' => now()]);
    }
}
