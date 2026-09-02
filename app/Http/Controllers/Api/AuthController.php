<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use App\Services\AcademicSelectionService;
use App\Services\EmailVerificationService;
use App\Services\SingleLoginSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $r, EmailVerificationService $verificationService): JsonResponse
    {
        $r->merge(['email' => Str::lower(trim((string) $r->input('email')))]);
        $d = $r->validate(['name' => 'required|string|max:150', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:8|confirmed', 'verification_grant' => ['required', 'string', 'size:64'], 'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('status', 'active')], 'exam_board_id' => ['required', 'integer', Rule::exists('exam_boards', 'id')->where('status', 'active')], 'school_year' => 'nullable|string|max:40', 'school_name' => 'nullable|string|max:180', 'subject_ids' => 'required|array|min:1', 'subject_ids.*' => ['integer', Rule::exists('subjects', 'id')->where('status', 'active')]]);
        $selection = app(AcademicSelectionService::class)->validate($d);
        $d = [...$d, ...$selection];
        $u = DB::transaction(function () use ($d, $verificationService) {
            $challenge = $verificationService->consumeGrant(
                $d['verification_grant'],
                EmailVerificationService::PURPOSE_STUDENT_SIGNUP,
            );
            if ($challenge->email !== $d['email']) {
                throw ValidationException::withMessages(['email' => 'Verify the same email address used for registration.']);
            }

            $u = User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => $d['password'], 'email_verified_at' => now()]);
            app(RoleAssignmentService::class)->assign($u, 'student');
            $p = app(StudentOwnershipService::class)->createPlatformStudent($u, Arr::only($d, [
                'course_id',
                'exam_board_id',
                'school_year',
                'school_name',
            ]));
            $p->subjects()->sync($d['subject_ids'] ?? []);
            return $u;
        });
        return response()->json([
            'data' => $this->accessUser($u),
            'token' => app(SingleLoginSessionService::class)->issueApiToken($u, 'web'),
        ], 201);
    }

    public function login(Request $r): JsonResponse
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required']);
        $u = User::query()->where('email', $d['email'])->first();
        abort_unless($u && $u->status->value === 'active' && $u->password && Hash::check($d['password'], $u->password), 422, 'Invalid credentials');
        $u->update(['last_login_at' => now()]);
        return response()->json([
            'data' => $this->accessUser($u),
            'token' => app(SingleLoginSessionService::class)->issueApiToken($u, $r->input('device_name', 'api')),
        ]);
    }

    public function logout(Request $r): JsonResponse
    {
        $token = $r->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        // Social/web authentication may have a session in addition to an API
        // token. Guard this path so bearer-token API logout remains stateless.
        if ($r->hasSession()) {
            auth()->guard('web')->logout();
            $r->session()->invalidate();
            $r->session()->regenerateToken();
        }

        Auth::guard('sanctum')->forgetUser();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $r): JsonResponse
    {
        return response()->json(['data' => $this->accessUser($r->user())]);
    }

    private function accessUser(User $user): User
    {
        return $user->loadAccessContext();
    }
}
