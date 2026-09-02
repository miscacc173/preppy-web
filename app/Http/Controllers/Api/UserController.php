<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends CrudController
{
    protected string $model = User::class;
    protected array $searchable = ['name'];
    protected array $storeRules = ['name' => 'required|string|max:150', 'email' => 'required|email|max:190|unique:users,email', 'password' => 'nullable|string|min:8', 'status' => 'sometimes|in:pending,active,suspended,disabled', 'timezone' => 'nullable|string|max:64', 'locale' => 'nullable|string|max:12'];
    protected array $updateRules = ['name' => 'sometimes|string|max:150', 'email' => 'sometimes|email|max:190|unique:users,email', 'password' => 'nullable|string|min:8', 'status' => 'sometimes|in:pending,active,suspended,disabled', 'timezone' => 'nullable|string|max:64', 'locale' => 'nullable|string|max:12'];
    protected array $with = ['roles'];

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasSystemRole('system_admin', 'system_staff'), 403);
        return parent::index($request);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        return parent::store($request);
    }

    public function show(int $id): JsonResponse
    {
        abort_unless(request()->user()->hasSystemRole('system_admin', 'system_staff'), 403);
        return parent::show($id);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        return parent::update($request, $id);
    }

    public function destroy(int $id): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('users.manage'), 403);
        return parent::destroy($id);
    }
}
