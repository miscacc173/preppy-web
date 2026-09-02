<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\TeachingClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\EducatorAccessGrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeachingClassController extends Controller
{
    public function index(Institute $institute): JsonResponse
    {
        $this->authorizeManage($institute);
        return response()->json(['data' => TeachingClass::query()->where('institute_id', $institute->id)->with(['course:id,name', 'subject:id,name', 'teacher:id,name,email', 'students:id,name,email'])->latest()->get()]);
    }

    public function store(Request $request, Institute $institute): JsonResponse
    {
        $this->authorizeManage($institute);
        $data = $this->validated($request, $institute);
        $studentIds = $data['student_user_ids'] ?? [];
        unset($data['student_user_ids']);
        $class = TeachingClass::create([...$data, 'institute_id' => $institute->id]);
        $class->students()->sync($studentIds);
        $this->syncTeacherGrant($class, $request);
        return response()->json(['data' => $class->load(['course:id,name', 'subject:id,name', 'teacher:id,name,email', 'students:id,name,email'])], 201);
    }

    public function update(Request $request, Institute $institute, TeachingClass $teachingClass): JsonResponse
    {
        $this->authorizeManage($institute);
        abort_unless($teachingClass->institute_id === $institute->id, 404);
        $data = $this->validated($request, $institute);
        $studentIds = $data['student_user_ids'] ?? null;
        unset($data['student_user_ids']);
        $teachingClass->update($data);
        if ($studentIds !== null) $teachingClass->students()->sync($studentIds);
        $this->syncTeacherGrant($teachingClass, $request);
        return response()->json(['data' => $teachingClass->fresh()->load(['course:id,name', 'subject:id,name', 'teacher:id,name,email', 'students:id,name,email'])]);
    }

    public function destroy(Institute $institute, TeachingClass $teachingClass): JsonResponse
    {
        $this->authorizeManage($institute);
        abort_unless($teachingClass->institute_id === $institute->id, 404);
        $teachingClass->students()->detach();
        EducatorAccessGrant::query()->where(['scope_type' => 'class', 'scope_id' => $teachingClass->id])->update(['status' => 'revoked', 'revoked_at' => now()]);
        $teachingClass->delete();
        return response()->json(status: 204);
    }

    private function authorizeManage(Institute $institute): void
    {
        $user = request()->user();
        abort_unless($user->hasSystemRole('system_admin', 'system_staff') || $user->hasRole('institute_admin', 'institute', $institute->id), 403);
    }

    private function validated(Request $request, Institute $institute): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'], 'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'], 'teacher_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'in:active,inactive'], 'student_user_ids' => ['sometimes', 'array'],
            'student_user_ids.*' => ['integer', 'exists:users,id'],
        ]);
        if (! empty($data['teacher_user_id'])) {
            $teacher = User::findOrFail($data['teacher_user_id']);
            abort_unless($teacher->hasRole('teacher') && in_array($institute->id, $teacher->activeInstituteMembershipIds(), true), 422, 'The selected teacher is not active in this institute.');
        }
        $studentIds = collect($data['student_user_ids'] ?? [])->unique();
        if ($studentIds->isNotEmpty()) {
            $validCount = StudentProfile::query()->where('institute_id', $institute->id)->where('status', 'active')->whereIn('user_id', $studentIds)->count();
            abort_unless($validCount === $studentIds->count(), 422, 'Every selected student must be active in this institute.');
        }
        return $data;
    }

    private function syncTeacherGrant(TeachingClass $class, Request $request): void
    {
        EducatorAccessGrant::query()->where(['scope_type' => 'class', 'scope_id' => $class->id])
            ->when($class->teacher_user_id, fn ($query) => $query->where('educator_user_id', '!=', $class->teacher_user_id))
            ->update(['status' => 'revoked', 'revoked_at' => now()]);
        if ($class->teacher_user_id) {
            EducatorAccessGrant::updateOrCreate(
                ['educator_user_id' => $class->teacher_user_id, 'scope_type' => 'class', 'scope_id' => $class->id],
                ['institute_id' => $class->institute_id, 'status' => 'active', 'revoked_at' => null, 'granted_by' => $request->user()->id],
            );
        }
    }
}
