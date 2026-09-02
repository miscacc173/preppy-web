<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EducatorAccessGrant;
use App\Models\TeachingClass;
use App\Models\User;
use App\Services\EducatorAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EducatorAccessController extends Controller
{
    public function index(Request $request, User $educator): JsonResponse
    {
        $instituteId = $request->integer('institute_id') ?: null;
        abort_unless(app(EducatorAccessService::class)->canManage($request->user(), $educator, $instituteId), 403);
        return response()->json(['data' => EducatorAccessGrant::query()->where('educator_user_id', $educator->id)->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId))->latest()->get()]);
    }

    public function store(Request $request, User $educator): JsonResponse
    {
        abort_unless($educator->hasRole('teacher') || $educator->hasRole('tutor'), 422, 'The selected user is not an educator.');
        $data = $request->validate(['institute_id' => ['nullable', 'integer', 'exists:institutes,id'], 'scope_type' => ['required', Rule::in(['class', 'course', 'student', 'subject'])], 'scope_id' => ['required', 'integer', 'min:1'], 'can_manage_parents' => ['sometimes', 'boolean']]);
        abort_unless(app(EducatorAccessService::class)->canManage($request->user(), $educator, $data['institute_id'] ?? null), 403);
        $this->validateScope($data['scope_type'], (int) $data['scope_id'], $data['institute_id'] ?? null);
        abort_if(($data['can_manage_parents'] ?? false) && ! $educator->hasRole('teacher'), 422, 'Only teachers can receive parent-management access.');
        $grant = EducatorAccessGrant::updateOrCreate(['educator_user_id' => $educator->id, 'scope_type' => $data['scope_type'], 'scope_id' => $data['scope_id']], [...$data, 'can_manage_parents' => $data['can_manage_parents'] ?? false, 'status' => 'active', 'revoked_at' => null, 'granted_by' => $request->user()->id]);
        return response()->json(['data' => $grant], $grant->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, User $educator, EducatorAccessGrant $grant): JsonResponse
    {
        abort_unless($grant->educator_user_id === $educator->id && app(EducatorAccessService::class)->canManage($request->user(), $educator, $grant->institute_id), 403);
        $grant->update(['status' => 'revoked', 'revoked_at' => now()]);
        return response()->json(status: 204);
    }

    private function validateScope(string $type, int $id, ?int $instituteId): void
    {
        $valid = match ($type) {
            'class' => TeachingClass::query()->where('id', $id)->when($instituteId, fn ($q) => $q->where('institute_id', $instituteId))->exists(),
            'student' => User::query()->where('id', $id)->whereHas('studentProfile', fn ($q) => $q->when($instituteId, fn ($qq) => $qq->where('institute_id', $instituteId)))->exists(),
            'course' => \App\Models\Course::query()->whereKey($id)->exists(),
            'subject' => \App\Models\Subject::query()->whereKey($id)->exists(),
        };
        abort_unless($valid, 422, 'The selected access scope is invalid.');
    }
}
