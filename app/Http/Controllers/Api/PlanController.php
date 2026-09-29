<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use App\Models\PlanCourseSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanController extends CrudController
{
    protected string $model = Plan::class;
    protected array $searchable = ['name', 'code'];
    protected array $with = ['course', 'subjects', 'courseSubjectSelections.course', 'courseSubjectSelections.subject'];
    protected array $storeRules = [
        'name' => 'required|string|max:150', 'code' => 'required|string|max:100|unique:plans,code',
        'scope' => 'sometimes|in:subject,multi_subject,course,all_subjects,institute',
        'billing_interval' => 'required|in:month,quarter,year', 'billing_interval_count' => 'required|integer|min:1',
        'price_minor' => 'required|integer|min:0', 'currency' => 'required|string|size:3', 'trial_days' => 'integer|min:0',
        'is_public' => 'boolean', 'is_active' => 'boolean', 'features' => 'array', 'metadata' => 'array',
        'course_subjects' => 'required|array|min:1',
        'course_subjects.*.course_id' => 'required|integer|exists:courses,id',
        'course_subjects.*.subject_ids' => 'required|array|min:1',
        'course_subjects.*.subject_ids.*' => 'integer|exists:subjects,id',
    ];
    protected array $updateRules = [];

    public function store(Request $request): JsonResponse
    {
        [$data, $groups] = $this->validatedPlan($request, $this->storeRules);
        $plan = DB::transaction(function () use ($data, $groups): Plan {
            $plan = Plan::create($data);
            $this->syncCourseSubjects($plan, $groups);
            return $plan;
        });
        return response()->json(['data' => $plan->load($this->with)], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        [$data, $groups] = $this->validatedPlan($request, $this->rulesForUpdate($id));
        DB::transaction(function () use ($plan, $data, $groups): void {
            $plan->update($data);
            $this->syncCourseSubjects($plan, $groups);
        });
        return response()->json(['data' => $plan->fresh()->load($this->with)]);
    }

    protected function rulesForUpdate(int $id): array
    {
        $rules = $this->storeRules;
        $rules['code'] = 'required|string|max:100|unique:plans,code,'.$id;
        return $rules;
    }

    /** @return array{0: array<string,mixed>, 1: array<int,array{course_id:int,subject_ids:array<int>}>} */
    private function validatedPlan(Request $request, array $rules): array
    {
        $data = $request->validate($rules);
        $groups = collect($data['course_subjects'] ?? [])->map(function (array $group): array {
            return [
                'course_id' => (int) $group['course_id'],
                'subject_ids' => collect($group['subject_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all(),
            ];
        })->unique('course_id')->values()->all();

        $errors = [];
        foreach ($groups as $index => $group) {
            if ($group['subject_ids'] === []) {
                $errors["course_subjects.$index.subject_ids"] = ['Each selected course must contain at least one subject.'];
                continue;
            }
            $valid = DB::table('course_subjects')->where('course_id', $group['course_id'])->whereIn('subject_id', $group['subject_ids'])->pluck('subject_id')->map(fn ($id) => (int) $id)->all();
            $invalid = array_values(array_diff($group['subject_ids'], $valid));
            if ($invalid !== []) {
                $errors["course_subjects.$index.subject_ids"] = ['Every selected subject must belong to its selected course.'];
            }
        }
        if ($errors !== []) throw ValidationException::withMessages($errors);

        $allSubjectIds = collect($groups)->flatMap(fn ($group) => $group['subject_ids'])->unique()->values()->all();
        $courseIds = collect($groups)->pluck('course_id')->unique()->values()->all();
        if ($courseIds === [] || $allSubjectIds === []) {
            throw ValidationException::withMessages(['course_subjects' => 'Select at least one course and at least one subject for each selected course.']);
        }

        unset($data['course_subjects']);
        $data['trial_days'] = 0;
        // Keep legacy fields populated for older billing/entitlement code while
        // plan_course_subjects is the authoritative course-first mapping.
        $data['course_id'] = count($courseIds) === 1 ? $courseIds[0] : null;
        $data['scope'] = count($allSubjectIds) === 1 ? 'subject' : 'multi_subject';
        return [$data, $groups];
    }

    private function syncCourseSubjects(Plan $plan, array $groups): void
    {
        PlanCourseSubject::query()->where('plan_id', $plan->id)->delete();
        $subjectIds = [];
        foreach ($groups as $group) {
            foreach ($group['subject_ids'] as $subjectId) {
                PlanCourseSubject::create(['plan_id' => $plan->id, 'course_id' => $group['course_id'], 'subject_id' => $subjectId]);
                $subjectIds[] = $subjectId;
            }
        }
        $plan->subjects()->sync(array_values(array_unique($subjectIds)));
    }
}
