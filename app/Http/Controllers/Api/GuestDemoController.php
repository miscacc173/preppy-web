<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertGuestDemoRequest;
use App\Http\Requests\StartGuestDemoRequest;
use App\Http\Requests\UpdateGuestDemoProgressRequest;
use App\Models\GuestDemoSession;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\GuestConversionService;
use App\Services\GuestDemoAccessService;
use App\Services\GuestDemoEligibilityService;
use App\Services\RoleAssignmentService;
use App\Services\SingleLoginSessionService;
use App\Services\AcademicSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestDemoController extends Controller
{
    public function start(
        StartGuestDemoRequest $request,
        EmailVerificationService $verificationService,
        GuestDemoAccessService $accessService,
        GuestDemoEligibilityService $eligibilityService,
    ): JsonResponse {
        $data = $request->validated();
        $data = [...$data, ...app(AcademicSelectionService::class)->validate($data)];

        $guest = DB::transaction(function () use ($data, $verificationService, $accessService, $eligibilityService): array {
            $challenge = $verificationService->consumeGrant(
                $data['verification_grant'],
                EmailVerificationService::PURPOSE_GUEST_DEMO,
            );
            $email = $eligibilityService->claim($challenge->email);

            $accessToken = $accessService->createAccessToken();
            $metadata = $data['metadata'] ?? [];
            $metadata['course_id'] = $data['course_id'] ?? null;
            $metadata['exam_board_id'] = $data['exam_board_id'] ?? null;
            $metadata['subject_ids'] = $data['subject_ids'];

            $session = GuestDemoSession::create([
                'email' => $email,
                'email_verified_at' => now(),
                'status' => 'active',
                'access_token_hash' => $accessService->hashAccessToken($accessToken),
                'started_at' => now(),
                'expires_at' => now()->addDays(7),
                'progress_snapshot' => [],
                'metadata' => $metadata,
            ]);
            $eligibilityService->bindSession($email, $session->id);

            return ['session' => $session, 'access_token' => $accessToken];
        });

        return response()->json([
            'data' => $guest['session'],
            'access_token' => $guest['access_token'],
        ], 201);
    }

    public function show(
        Request $request,
        GuestDemoSession $guest,
        GuestDemoAccessService $accessService,
    ): JsonResponse {
        $accessService->authorize($request, $guest);

        return response()->json(['data' => $guest]);
    }

    public function progress(
        UpdateGuestDemoProgressRequest $request,
        GuestDemoSession $guest,
        GuestDemoAccessService $accessService,
    ): JsonResponse {
        $accessService->authorize($request, $guest);

        if ($guest->converted_at) {
            throw ValidationException::withMessages(['session' => 'Converted guest demo sessions can no longer be updated.']);
        }

        $guest->update($request->validated());

        return response()->json(['data' => $guest->fresh()]);
    }

    public function convert(
        ConvertGuestDemoRequest $request,
        GuestDemoSession $guest,
        GuestDemoAccessService $accessService,
    ): JsonResponse {
        $accessService->authorize($request, $guest);
        $data = $request->validated();

        if (! $guest->email || ! $guest->email_verified_at) {
            throw ValidationException::withMessages(['session' => 'A verified guest email is required before account creation.']);
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($guest->email)])->exists()) {
            throw ValidationException::withMessages(['email' => 'An account already exists for this verified email. Please sign in instead.']);
        }

        $user = DB::transaction(function () use ($data, $guest): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($guest->email),
                'password' => $data['password'],
                'email_verified_at' => $guest->email_verified_at,
            ]);

            app(RoleAssignmentService::class)->assign($user, 'student');

            $metadata = is_array($guest->metadata) ? $guest->metadata : [];
            app(GuestConversionService::class)->convert(
                $guest,
                $user,
                [
                    ...Arr::only($data, ['school_year', 'school_name']),
                    'course_id' => $metadata['course_id'] ?? null,
                    'exam_board_id' => $metadata['exam_board_id'] ?? null,
                    'subject_ids' => $metadata['subject_ids'] ?? [],
                ],
            );

            return $user;
        });

        $user->load(['roles', 'studentProfile.subjects']);

        return response()->json([
            'data' => $user,
            'token' => app(SingleLoginSessionService::class)->issueApiToken($user, 'guest-conversion'),
        ], 201);
    }
}
