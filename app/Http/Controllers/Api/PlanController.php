<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanController extends CrudController
{
    protected string $model = Plan::class;
    protected array $searchable = ['name', 'code'];
    protected array $with = ['course', 'subjects'];
    protected array $storeRules = ['name' => 'required|string|max:150', 'code' => 'required|string|max:100|unique:plans,code', 'scope' => 'required|in:subject,multi_subject,course,all_subjects,institute', 'course_id' => 'nullable|exists:courses,id', 'billing_interval' => 'required|in:day,week,month,year', 'billing_interval_count' => 'required|integer|min:1', 'price_minor' => 'required|integer|min:0', 'currency' => 'required|string|size:3', 'trial_days' => 'integer|min:0', 'is_public' => 'boolean', 'is_active' => 'boolean', 'features' => 'array', 'metadata' => 'array', 'subject_ids' => 'array', 'subject_ids.*' => 'exists:subjects,id'];
    protected array $updateRules = ['name' => 'required|string|max:150', 'code' => 'required|string|max:100|unique:plans,code', 'scope' => 'required|in:subject,multi_subject,course,all_subjects,institute', 'course_id' => 'nullable|exists:courses,id', 'billing_interval' => 'required|in:day,week,month,year', 'billing_interval_count' => 'required|integer|min:1', 'price_minor' => 'required|integer|min:0', 'currency' => 'required|string|size:3', 'trial_days' => 'integer|min:0', 'is_public' => 'boolean', 'is_active' => 'boolean', 'features' => 'array', 'metadata' => 'array', 'subject_ids' => 'array', 'subject_ids.*' => 'exists:subjects,id'];

    public function store(Request $request): JsonResponse
    {
        [$data, $ids] = $this->validatedPlan($request, $this->storeRules);
        $m = DB::transaction(function () use ($data, $ids): Plan {
            $plan = Plan::create($data);
            $plan->subjects()->sync($ids);
            return $plan;
        });
        return response()->json(['data' => $m->load(['course', 'subjects'])], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $m = Plan::findOrFail($id);
        [$data, $ids] = $this->validatedPlan($request, $this->rulesForUpdate($id));
        DB::transaction(function () use ($m, $data, $ids): void {
            $m->update($data);
            $m->subjects()->sync($ids);
        });
        return response()->json(['data' => $m->fresh()->load(['course', 'subjects'])]);
    }

    /** @return array{0: array<string, mixed>, 1: array<int>} */
    private function validatedPlan(Request $request, array $rules): array
    {
        $data = $request->validate($rules);
        $scope = $data['scope'];
        $ids = collect($data['subject_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : null;

        $errors = [];
        if ($scope === 'subject' && count($ids) !== 1) {
            $errors['subject_ids'] = ['A subject plan must contain exactly one subject.'];
        } elseif ($scope === 'multi_subject' && count($ids) < 2) {
            $errors['subject_ids'] = ['A multiple-subject plan must contain at least two subjects.'];
        } elseif ($scope === 'course' && ! $courseId) {
            $errors['course_id'] = ['A course must be selected for a course plan.'];
        }
        if ($scope !== 'course' && $courseId) {
            $errors['course_id'] = ['A course can only be selected for a course plan.'];
        }
        if (! in_array($scope, ['subject', 'multi_subject'], true) && $ids !== []) {
            $errors['subject_ids'] = ['Subjects can only be selected for subject or multiple-subject plans.'];
        }
        if ($errors !== []) throw ValidationException::withMessages($errors);

        unset($data['subject_ids']);
        $data['trial_days'] = 0;
        $data['course_id'] = $scope === 'course' ? $courseId : null;
        return [$data, in_array($scope, ['subject', 'multi_subject'], true) ? $ids : []];
    }
}
