<?php

namespace App\Services;

use App\Models\EducatorAccessGrant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EducatorAccessService
{
    public function canManage(User $actor, User $educator, ?int $instituteId): bool
    {
        if ($actor->hasSystemRole('system_admin')) return true;
        if (! $educator->hasRole('teacher') || ! $instituteId) return false;
        return $actor->hasRole('institute_admin', 'institute', $instituteId)
            && in_array($instituteId, $educator->activeInstituteMembershipIds(), true);
    }

    public function activeGrants(User $educator): Collection
    {
        $instituteIds = $educator->activeInstituteMembershipIds();
        return EducatorAccessGrant::query()->where(['educator_user_id' => $educator->id, 'status' => 'active'])
            ->where(fn ($query) => $query->whereNull('institute_id')->orWhereIn('institute_id', $instituteIds))
            ->get();
    }

    /** @return array<int> */
    public function studentIds(User $teacher): array
    {
        if (! $teacher->hasRole('teacher')) return [];
        $ids = $this->studentIdsForGrants($this->activeGrants($teacher));
        return DB::table('student_profiles')->whereIn('user_id', $ids)->where('status', 'active')->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function canViewStudent(User $teacher, int $studentUserId): bool
    {
        return in_array($studentUserId, $this->studentIds($teacher), true);
    }

    public function canManageParentsForStudent(User $teacher, int $studentUserId): bool
    {
        if (! $teacher->hasRole('teacher') || ! $this->canViewStudent($teacher, $studentUserId)) return false;

        return $this->activeGrants($teacher)
            ->where('can_manage_parents', true)
            ->isNotEmpty()
            && in_array($studentUserId, $this->studentIdsForGrants(
                $this->activeGrants($teacher)->where('can_manage_parents', true)
            ), true);
    }

    /** @return array<int> */
    private function studentIdsForGrants(Collection $grants): array
    {
        $ids = collect();
        foreach ($grants as $grant) {
            if ($grant->scope_type === 'student') {
                $query = DB::table('student_profiles')->where('user_id', $grant->scope_id);
                if ($grant->institute_id) $query->where('institute_id', $grant->institute_id);
                $ids->push(...$query->pluck('user_id'));
            } elseif ($grant->scope_type === 'class') {
                $query = DB::table('teaching_class_students')
                    ->join('teaching_classes', 'teaching_classes.id', '=', 'teaching_class_students.teaching_class_id')
                    ->where('teaching_class_students.teaching_class_id', $grant->scope_id)
                    ->where('teaching_classes.status', 'active');
                if ($grant->institute_id) $query->where('teaching_classes.institute_id', $grant->institute_id);
                $ids->push(...$query->pluck('teaching_class_students.student_user_id'));
            } elseif ($grant->scope_type === 'course') {
                $query = DB::table('student_profiles')->where('course_id', $grant->scope_id);
                if ($grant->institute_id) $query->where('institute_id', $grant->institute_id);
                $ids->push(...$query->pluck('user_id'));
            } elseif ($grant->scope_type === 'subject') {
                $query = DB::table('student_subjects')
                    ->join('student_profiles', 'student_profiles.id', '=', 'student_subjects.student_profile_id')
                    ->where('student_subjects.subject_id', $grant->scope_id);
                if ($grant->institute_id) $query->where('student_profiles.institute_id', $grant->institute_id);
                $ids->push(...$query->pluck('student_profiles.user_id'));
            }
        }
        return $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** @return array<int> */
    public function subjectIds(User $educator): array
    {
        $grants = $this->activeGrants($educator);
        $ids = $grants->where('scope_type', 'subject')->pluck('scope_id');
        $courseIds = $grants->where('scope_type', 'course')->pluck('scope_id');
        if ($courseIds->isNotEmpty()) $ids->push(...DB::table('course_subjects')->whereIn('course_id', $courseIds)->pluck('subject_id'));
        $classIds = $grants->where('scope_type', 'class')->pluck('scope_id');
        if ($classIds->isNotEmpty()) $ids->push(...DB::table('teaching_classes')->whereIn('id', $classIds)->where('status', 'active')->whereNotNull('subject_id')->pluck('subject_id'));
        $studentIds = $grants->where('scope_type', 'student')->pluck('scope_id');
        if ($studentIds->isNotEmpty()) $ids->push(...DB::table('student_subjects')->join('student_profiles', 'student_profiles.id', '=', 'student_subjects.student_profile_id')->whereIn('student_profiles.user_id', $studentIds)->pluck('student_subjects.subject_id'));
        return $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}
