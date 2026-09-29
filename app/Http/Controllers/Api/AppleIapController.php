<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Services\AppleIap\AppleIapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppleIapController extends Controller
{
    public function products(AppleIapService $service): JsonResponse
    {
        return response()->json(['data' => $service->products(), 'enabled' => (bool) config('payments.apple_iap.enabled', false)]);
    }

    public function verify(Request $request, AppleIapService $service): JsonResponse
    {
        $data = $request->validate([
            'student_profile_id' => ['required', 'exists:student_profiles,id'],
            'transaction_id' => ['required', 'string', 'max:255'],
            'product_id' => ['required', 'string', 'max:255'],
            'verification_data' => ['nullable', 'string', 'max:200000'],
        ]);
        $student = StudentProfile::findOrFail($data['student_profile_id']);
        return response()->json(['data' => $service->verifyAndApply($request->user()->id, $student, $data['transaction_id'], $data['product_id'])]);
    }

    public function notifications(Request $request, AppleIapService $service): JsonResponse
    {
        $data = $request->validate(['signedPayload' => ['required', 'string', 'max:500000']]);
        $service->processNotification($data['signedPayload']);
        return response()->json(['ok' => true]);
    }
}
