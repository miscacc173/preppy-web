<?php

namespace App\Services;

use App\Enums\PlanScope;
use App\Models\Plan;
use App\Models\StudentProfile;
use Illuminate\Validation\ValidationException;

class PlanEligibilityService
{
    public function isApplicable(StudentProfile $student, Plan $plan): bool
    {
        return match ($plan->scope) {
            PlanScope::AllSubjects => true,
            PlanScope::Course => $student->course_id !== null
                && $plan->course_id !== null
                && (int) $student->course_id === (int) $plan->course_id,
            PlanScope::Subject, PlanScope::MultiSubject => $this->hasMatchingSubject($student, $plan),
            PlanScope::Institute => $student->institute_id !== null,
        };
    }

    public function ensureApplicable(StudentProfile $student, Plan $plan): void
    {
        if (! $this->isApplicable($student, $plan)) {
            throw ValidationException::withMessages([
                'plan_id' => 'This plan is not available for the student’s selected course or subjects.',
            ]);
        }
    }

    private function hasMatchingSubject(StudentProfile $student, Plan $plan): bool
    {
        $studentSubjectIds = $student->subjects()->pluck('subjects.id');

        return $plan->subjects()->whereIn('subjects.id', $studentSubjectIds)->exists();
    }
}
