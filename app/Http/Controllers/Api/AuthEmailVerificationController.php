<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestSignupEmailVerificationRequest;
use App\Http\Requests\VerifySignupEmailRequest;
use App\Models\EmailVerificationChallenge;
use App\Services\DevelopmentEmailVerificationBypassService;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;

class AuthEmailVerificationController extends Controller
{
    public function request(
        RequestSignupEmailVerificationRequest $request,
        EmailVerificationService $verificationService,
        DevelopmentEmailVerificationBypassService $developmentBypass,
    ): JsonResponse {
        $email = $request->string('email')->toString();

        if ($developmentBypass->enabled()) {
            $verified = $developmentBypass->issueGrant($email, EmailVerificationService::PURPOSE_STUDENT_SIGNUP);

            return response()->json([
                'data' => [
                    'id' => $verified['challenge']->id,
                    'email' => $verified['challenge']->email,
                    'expires_at' => $verified['challenge']->expires_at,
                    'resend_available_at' => $verified['challenge']->resend_available_at,
                    'development_bypass' => true,
                    'verification_grant' => $verified['grant'],
                    'verification_grant_expires_at' => $verified['challenge']->verification_grant_expires_at,
                ],
            ], 201);
        }

        $challenge = $verificationService->requestChallenge($email, EmailVerificationService::PURPOSE_STUDENT_SIGNUP);

        return response()->json([
            'data' => [
                'id' => $challenge->id,
                'email' => $challenge->email,
                'expires_at' => $challenge->expires_at,
                'resend_available_at' => $challenge->resend_available_at,
                'development_bypass' => false,
            ],
        ], 201);
    }

    public function verify(
        VerifySignupEmailRequest $request,
        EmailVerificationChallenge $challenge,
        EmailVerificationService $verificationService,
    ): JsonResponse {
        $verified = $verificationService->verify(
            $challenge,
            $request->string('code')->toString(),
            EmailVerificationService::PURPOSE_STUDENT_SIGNUP,
        );

        return response()->json([
            'data' => [
                'email' => $verified['challenge']->email,
                'verification_grant' => $verified['grant'],
                'verification_grant_expires_at' => $verified['challenge']->verification_grant_expires_at,
            ],
        ]);
    }
}
