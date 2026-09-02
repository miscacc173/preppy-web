<?php

namespace App\Http\Controllers\Api;

use App\Models\Institute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstituteController extends CrudController
{
    protected string $model = Institute::class;
    protected array $searchable = ['name', 'code', 'slug'];
    protected array $storeRules = ['name' => 'required|string|max:180', 'slug' => 'required|string|max:180|unique:institutes,slug', 'code' => 'required|string|max:60|unique:institutes,code', 'status' => 'sometimes|in:pending,active,suspended,closed', 'billing_email' => 'nullable|email', 'phone' => 'nullable|string|max:40', 'address' => 'nullable|string', 'city' => 'nullable|string|max:100', 'country_code' => 'nullable|string|size:2', 'timezone' => 'nullable|string|max:64', 'settings' => 'array'];
    protected array $updateRules = ['name' => 'required|string|max:180', 'slug' => 'required|string|max:180|unique:institutes,slug', 'code' => 'required|string|max:60|unique:institutes,code', 'status' => 'sometimes|in:pending,active,suspended,closed', 'billing_email' => 'nullable|email', 'phone' => 'nullable|string|max:40', 'address' => 'nullable|string', 'city' => 'nullable|string|max:100', 'country_code' => 'nullable|string|size:2', 'timezone' => 'nullable|string|max:64', 'settings' => 'array'];

    public function index(Request $request): JsonResponse
    {
        $query = Institute::query();
        if (! $request->user()->hasSystemRole('system_admin', 'system_staff')) {
            $query->whereIn('id', $request->user()->instituteContextIds());
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%")->orWhere('slug', 'ilike', "%{$search}%"));
        }
        return response()->json($query->latest('id')->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('institutes.manage'), 403);
        return parent::store($request);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorizeInstitute($id);
        return parent::show($id);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeInstitute($id, true);
        $institute = Institute::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'slug' => ['required', 'string', 'max:180', Rule::unique('institutes', 'slug')->ignore($institute->id)],
            'code' => ['required', 'string', 'max:60', Rule::unique('institutes', 'code')->ignore($institute->id)],
            'status' => 'sometimes|in:pending,active,suspended,closed',
            'billing_email' => 'nullable|email',
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|size:2',
            'timezone' => 'nullable|string|max:64',
            'settings' => 'array',
        ]);
        $institute->update($data);
        return response()->json(['data' => $institute->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('institutes.manage'), 403);
        return parent::destroy($id);
    }

    private function authorizeInstitute(int $id, bool $manage = false): void
    {
        $user = request()->user();
        abort_unless($user->canAccessInstitute($id), 403);
        if ($manage && ! $user->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($user->hasRole('institute_admin', 'institute', $id), 403);
        }
    }
}
