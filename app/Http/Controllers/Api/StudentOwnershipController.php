<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\StudentProfile;
use App\Services\StudentOwnershipService;
use Illuminate\Http\JsonResponse;

class StudentOwnershipController extends Controller
{
    public function attach(StudentProfile $student, Institute $institute): JsonResponse
    {
        abort_unless(request()->user()->canAccessInstitute($institute->id), 403);
        abort_unless(request()->user()->hasSystemRole('system_admin', 'system_staff') || request()->user()->hasPermission('institutes.students.manage'), 403);
        return response()->json(['data' => app(StudentOwnershipService::class)->attachExistingStudent($student, $institute, request()->user()->id)]);
    }

    public function detach(StudentProfile $student): JsonResponse
    {
        abort_unless($student->institute_id && request()->user()->canAccessInstitute($student->institute_id), 403);
        abort_unless(request()->user()->hasSystemRole('system_admin', 'system_staff') || request()->user()->hasPermission('institutes.students.manage'), 403);
        return response()->json(['data' => app(StudentOwnershipService::class)->detachToPlatform($student, request()->user()->id)]);
    }
}
