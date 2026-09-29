<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\RoleAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserRoleController extends Controller
{
    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->hasPermission('roles.manage'), 403);
        $data = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*.slug' => ['required', 'string', 'exists:roles,slug'],
            'roles.*.context_type' => ['required', 'in:system,institute'],
            'roles.*.context_id' => ['nullable', 'integer', 'exists:institutes,id'],
        ]);

        $roleScopes = Role::query()->whereIn('slug', collect($data['roles'])->pluck('slug'))->pluck('scope', 'slug');
        foreach ($data['roles'] as $index => $assignment) {
            $expected = $roleScopes[$assignment['slug']] === 'institute' ? 'institute' : 'system';
            if ($assignment['context_type'] !== $expected || ($expected === 'institute' && empty($assignment['context_id']))) {
                throw ValidationException::withMessages(["roles.{$index}.context_type" => "The {$assignment['slug']} role requires {$expected} context."]);
            }
        }

        DB::transaction(function () use ($request, $user, $data): void {
            DB::table('user_roles')->where('user_id', $user->id)->delete();
            foreach ($data['roles'] as $assignment) {
                app(RoleAssignmentService::class)->assign(
                    $user,
                    $assignment['slug'],
                    $assignment['context_type'],
                    $assignment['context_id'] ?? null,
                    $request->user()->id,
                );
            }
        });

        return response()->json(['data' => $user->fresh()->load('roles')]);
    }
}
