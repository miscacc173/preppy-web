<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\StudentProfile;
use Illuminate\Validation\ValidationException;

class PlanEligibilityService
{
    public function isApplicable(StudentProfile $student, Plan $plan): bool
    {
        if (! $plan->is_active) return false;
        if (! $plan->is_public) {
            if (! $student->institute_id) return false;
            $hasInstituteAccess = \App\Models\InstitutePlanPrice::query()
                ->where('plan_id', $plan->id)
                ->where('institute_id', $student->institute_id)
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->exists();
            if (! $hasInstituteAccess) return false;
        }

        $plan->loadMissing('courseSubjectSelections');
        if ($plan->courseSubjectSelections->isNotEmpty()) {
            $courseId = (int) ($student->course_id ?? 0);
            if (! $courseId) return false;
            $allowedSubjectIds = $plan->courseSubjectSelections
                ->where('course_id', $courseId)
                ->pluck('subject_id')->map(fn ($id) => (int) $id)->all();
            if ($allowedSubjectIds === []) return false;
            $studentSubjectIds = $student->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
            return count(array_intersect($allowedSubjectIds, $studentSubjectIds)) > 0;
        }

        // Legacy fallback for plans created before course-first mapping existed.
        if ($plan->scope?->value === 'institute') return $student->institute_id !== null;
        if ($plan->scope?->value === 'all_subjects') return true;
        if ($plan->scope?->value === 'course') return $plan->course_id && (int) $plan->course_id === (int) $student->course_id;
        if ($plan->course_id && (int) $plan->course_id !== (int) $student->course_id) return false;
        $planSubjectIds = $plan->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
        if ($planSubjectIds === []) return false;
        $studentSubjectIds = $student->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
        return count(array_intersect($planSubjectIds, $studentSubjectIds)) > 0;
    }

    public function ensureApplicable(StudentProfile $student, Plan $plan): void
    {
        if (! $this->isApplicable($student, $plan)) {
            throw ValidationException::withMessages(['plan_id' => 'This plan is not available for the student’s selected course and subjects.']);
        }
    }
}
