<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;

class PermissionController extends CrudController
{
    protected string $model = Permission::class;
    protected array $searchable = ['name', 'slug', 'group'];
    protected array $storeRules = ['name' => 'required|string|max:120', 'slug' => 'required|string|max:120|unique:permissions,slug', 'group' => 'nullable|string|max:80'];
}
