<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_profile_id' => ['required', 'exists:student_profiles,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'code' => ['required', 'string', 'max:80'],
        ]);
        $student = StudentProfile::findOrFail($data['student_profile_id']);
        abort_unless((int) $student->user_id === (int) $request->user()->id || $request->user()->hasSystemRole('system_admin', 'system_staff'), 403);
        $plan = Plan::query()->where('is_active', true)->findOrFail($data['plan_id']);
        $preview = app(PromotionService::class)->preview($student, $plan, $data['code']);
        return response()->json(['data' => $preview]);
    }
}
