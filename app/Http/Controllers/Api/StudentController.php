<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        abort_unless($r->user()->hasPermission('students.view') || $r->user()->hasPermission('students.manage'), 403);
        $q = StudentProfile::with(['user', 'institute', 'course', 'subjects']);
        if (! $r->user()->hasSystemRole('system_admin', 'system_staff')) {
            $q->whereIn('institute_id', $r->user()->instituteContextIds());
        }
        if ($r->filled('institute_id')) $q->where('institute_id', $r->integer('institute_id'));
        if ($r->filled('ownership_type')) $q->where('ownership_type', $r->string('ownership_type'));
        return response()->json($q->latest()->paginate(min($r->integer('per_page', 20), 100)));
    }

    public function show(StudentProfile $student): JsonResponse
    {
        $this->authorizeStudent($student, false);
        return response()->json(['data' => $student->load(['user', 'institute', 'course', 'subjects'])]);
    }

    public function update(Request $r, StudentProfile $student): JsonResponse
    {
        $this->authorizeStudent($student, true);
        $d = $r->validate(['course_id' => 'nullable|exists:courses,id', 'exam_board_id' => 'nullable|exists:exam_boards,id', 'school_year' => 'nullable|string|max:40', 'school_name' => 'nullable|string|max:180', 'date_of_birth' => 'nullable|date', 'status' => 'sometimes|string|max:30', 'preferences' => 'array', 'metadata' => 'array', 'subject_ids' => 'array', 'subject_ids.*' => 'exists:subjects,id']);
        $ids = $d['subject_ids'] ?? null;
        unset($d['subject_ids']);
        $student->update($d);
        if ($ids !== null) $student->subjects()->sync($ids);
        return response()->json(['data' => $student->fresh()->load(['user', 'institute', 'course', 'subjects'])]);
    }

    public function destroy(StudentProfile $student): JsonResponse
    {
        $this->authorizeStudent($student, true);
        $student->delete();
        return response()->json([], 204);
    }

    private function authorizeStudent(StudentProfile $student, bool $manage): void
    {
        $user = request()->user();
        if ($user->hasSystemRole('system_admin', 'system_staff')) {
            abort_if($manage && ! $user->hasPermission('students.manage'), 403);
            return;
        }
        abort_unless($student->institute_id && $user->canAccessInstitute($student->institute_id), 403);
        if ($manage) abort_unless($user->hasPermission('institutes.students.manage'), 403);
    }
}
