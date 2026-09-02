<?php

namespace App\Services;

use App\Enums\StudentOwnershipType;
use App\Models\Institute;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentOwnershipService
{
    /**
     * Client-provided attributes that belong to the student_profiles table.
     * Ownership and identity columns are always set by this service.
     */
    private const PROFILE_FIELDS = [
        'course_id',
        'exam_board_id',
        'school_year',
        'school_name',
        'date_of_birth',
        'status',
        'preferences',
        'metadata',
    ];

    public function createPlatformStudent(User $user, array $profile = []): StudentProfile
    {
        return DB::transaction(function () use ($user, $profile) {
            if ($user->studentProfile()->exists()) throw ValidationException::withMessages(['user' => 'Student profile already exists.']);
            return StudentProfile::create([
                ...Arr::only($profile, self::PROFILE_FIELDS),
                'user_id' => $user->id,
                'ownership_type' => StudentOwnershipType::Platform,
                'institute_id' => null,
                'onboarded_at' => now(),
            ]);
        });
    }

    public function createInstituteStudent(User $user, Institute $institute, array $profile = []): StudentProfile
    {
        return DB::transaction(function () use ($user, $institute, $profile) {
            if ($user->studentProfile()->exists()) throw ValidationException::withMessages(['user' => 'Student profile already exists.']);
            return StudentProfile::create([
                ...Arr::only($profile, self::PROFILE_FIELDS),
                'user_id' => $user->id,
                'ownership_type' => StudentOwnershipType::Institute,
                'institute_id' => $institute->id,
                'onboarded_at' => now(),
            ]);
        });
    }

    public function attachExistingStudent(StudentProfile $student, Institute $institute, int $actorId): StudentProfile
    {
        return DB::transaction(function () use ($student, $institute, $actorId) {
            $locked = StudentProfile::lockForUpdate()->findOrFail($student->id);
            if ($locked->ownership_type === StudentOwnershipType::Institute && $locked->institute_id !== $institute->id) throw ValidationException::withMessages(['student' => 'Student already belongs to another institute.']);
            $before = $locked->toArray();
            $locked->update(['ownership_type' => StudentOwnershipType::Institute, 'institute_id' => $institute->id]);
            app(AuditService::class)->record($actorId, 'student.attached_to_institute', $locked, $before, $locked->fresh()->toArray());
            return $locked->fresh();
        });
    }

    public function detachToPlatform(StudentProfile $student, int $actorId): StudentProfile
    {
        return DB::transaction(function () use ($student, $actorId) {
            $locked = StudentProfile::lockForUpdate()->findOrFail($student->id);
            $before = $locked->toArray();
            $locked->update(['ownership_type' => StudentOwnershipType::Platform, 'institute_id' => null]);
            app(AuditService::class)->record($actorId, 'student.detached_from_institute', $locked, $before, $locked->fresh()->toArray());
            return $locked->fresh();
        });
    }
}
