<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class CrudController extends Controller
{
    protected string $model;
    protected array $storeRules = [];
    protected array $updateRules = [];
    protected array $with = [];
    protected array $searchable = [];

    public function index(Request $request): JsonResponse
    {
        $q = ($this->model)::query()->with($this->with);
        if ($s = $request->string('search')->toString()) $q->where(function ($x) use ($s) {
            foreach ($this->searchable as $i => $c) $i ? $x->orWhere($c, 'ilike', "%$s%") : $x->where($c, 'ilike', "%$s%");
        });
        return response()->json($q->latest('id')->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->storeRules);
        $m = ($this->model)::create($data);
        return response()->json(['data' => $m->load($this->with)], 201);
    }

    public function show(int $id): JsonResponse
    {
        $m = ($this->model)::with($this->with)->findOrFail($id);
        return response()->json(['data' => $m]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $m = ($this->model)::findOrFail($id);
        $m->update($request->validate($this->rulesForUpdate($id)));
        return response()->json(['data' => $m->fresh()->load($this->with)]);
    }

    /** Add the current model id to simple unique rules used by CRUD resources. */
    protected function rulesForUpdate(int $id): array
    {
        $rules = $this->updateRules ?: $this->storeRules;
        foreach ($rules as $field => $rule) {
            if (! is_string($rule)) continue;
            $parts = explode('|', $rule);
            foreach ($parts as &$part) {
                if (str_starts_with($part, 'unique:') && substr_count($part, ',') === 1) $part .= ','.$id;
            }
            $rules[$field] = implode('|', $parts);
        }
        return $rules;
    }

    public function destroy(int $id): JsonResponse
    {
        $m = ($this->model)::findOrFail($id);
        $m->delete();
        return response()->json([], 204);
    }
}
