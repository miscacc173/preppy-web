<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStudentRequest;
use App\Models\Institute;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstituteStudentController extends Controller
{
    public function available(Request $request, Institute $institute): JsonResponse
    {
        abort_unless($request->user()->canAccessInstitute($institute->id), 403);
        abort_unless(
            $request->user()->hasSystemRole('system_admin', 'system_staff')
            || $request->user()->hasPermission('institutes.students.manage'),
            403
        );
        $data = $request->validate(['email' => 'required|email']);
        $student = StudentProfile::query()
            ->where('ownership_type', 'platform')
            ->whereNull('institute_id')
            ->whereHas('user', fn ($query) => $query->whereRaw('LOWER(email) = ?', [strtolower($data['email'])]))
            ->with(['user', 'institute', 'course', 'examBoard', 'subjects'])
            ->first();

        return response()->json(['data' => $student]);
    }

    public function store(CreateStudentRequest $request, Institute $institute): JsonResponse
    {
        abort_unless($request->user()->canAccessInstitute($institute->id), 403);
        abort_unless(
            $request->user()->hasSystemRole('system_admin', 'system_staff')
            || $request->user()->hasPermission('institutes.students.manage'),
            403
        );
        $student = DB::transaction(function () use ($request, $institute) {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            app(RoleAssignmentService::class)->assign($user, 'student', 'institute', $institute->id, $request->user()?->id);
            $profile = app(StudentOwnershipService::class)->createInstituteStudent($user, $institute, $request->safe()->only(['course_id', 'exam_board_id', 'school_year', 'school_name']));
            $profile->subjects()->sync($request->input('subject_ids', []));
            return $profile->load(['user', 'subjects']);
        });
        return response()->json(['data' => $student], 201);
    }
}
