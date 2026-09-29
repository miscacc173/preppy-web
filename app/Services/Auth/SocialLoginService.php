<?php

namespace App\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialLoginService
{
    /**
     * @return array{0:User,1:bool,2:bool}
     */
    public function resolve(
        string $provider,
        string $providerUserId,
        ?string $email,
        ?string $name = null,
        ?string $avatarUrl = null,
        ?string $accessToken = null,
        ?string $refreshToken = null,
        ?int $expiresIn = null,
        array $metadata = [],
    ): array {
        return DB::transaction(function () use ($provider, $providerUserId, $email, $name, $avatarUrl, $accessToken, $refreshToken, $expiresIn, $metadata): array {
            $normalizedEmail = $email ? Str::lower(trim($email)) : null;
            $social = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            $user = $social ? User::withTrashed()->find($social->user_id) : null;
            if (! $user && $normalizedEmail) {
                $user = User::withTrashed()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
            }
            $newUser = ! $user;

            if ($user?->trashed()) {
                // Recover identities affected by the older destructive admin-delete flow,
                // but do not reactivate them without an administrator decision.
                $user->restore();
                $user->status = 'disabled';
                $user->save();
            }

            if (! $user) {
                if (! $normalizedEmail) {
                    throw ValidationException::withMessages([
                        'email' => 'The sign-in provider did not supply an email address for this new account. Please allow email sharing and try again.',
                    ]);
                }

                $user = User::create([
                    'name' => trim((string) $name) ?: 'Student',
                    'email' => $normalizedEmail,
                    'password' => Str::random(40),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]);
            }

            if ($user->status->value !== 'active') {
                return [$user, false, false];
            }

            SocialAccount::updateOrCreate(
                ['provider' => $provider, 'provider_user_id' => $providerUserId],
                [
                    'user_id' => $user->id,
                    'provider_email' => $normalizedEmail,
                    'avatar_url' => $avatarUrl,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                    'metadata' => $metadata,
                ]
            );

            if (! $user->studentProfile()->exists()) {
                app(RoleAssignmentService::class)->assign($user, 'student');
                app(StudentOwnershipService::class)->createPlatformStudent($user);
            }

            $profile = $user->studentProfile()->with('subjects')->first();
            $incompleteAcademicSetup = ! $profile?->course_id || ! $profile?->exam_board_id || $profile->subjects->isEmpty();

            return [$user, $newUser || $incompleteAcademicSetup, true];
        });
    }
}
