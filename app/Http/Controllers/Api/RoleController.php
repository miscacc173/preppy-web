<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends CrudController
{
    protected string $model = Role::class;
    protected array $searchable = ['name', 'slug'];
    protected array $with = ['permissions'];
    protected array $storeRules = ['name' => 'required|string|max:100', 'slug' => 'required|string|max:100|unique:roles,slug', 'scope' => 'required|in:system,institute', 'is_system' => 'boolean', 'permission_ids' => 'array', 'permission_ids.*' => 'exists:permissions,id'];

    public function store(Request $r): JsonResponse
    {
        $d = $r->validate($this->storeRules);
        $ids = $d['permission_ids'] ?? [];
        unset($d['permission_ids']);
        $m = Role::create($d);
        $m->permissions()->sync($ids);
        return response()->json(['data' => $m->load('permissions')], 201);
    }

    public function update(Request $r, int $id): JsonResponse
    {
        $m = Role::findOrFail($id);
        $d = $r->validate(['name' => 'sometimes|string|max:100', 'slug' => ['sometimes', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($m->id)], 'scope' => 'sometimes|in:system,institute', 'is_system' => 'boolean', 'permission_ids' => 'array', 'permission_ids.*' => 'exists:permissions,id']);
        $ids = $d['permission_ids'] ?? null;
        unset($d['permission_ids']);
        $m->update($d);
        if ($ids !== null) $m->permissions()->sync($ids);
        return response()->json(['data' => $m->fresh()->load('permissions')]);
    }
}
