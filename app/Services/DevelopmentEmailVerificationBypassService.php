<?php

namespace App\Services;

use App\Models\EmailVerificationChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevelopmentEmailVerificationBypassService
{
    public function enabled(): bool
    {
        return (bool) config('verification.email.development_bypass', false)
            && app()->environment(['local', 'development', 'testing']);
    }

    /**
     * @return array{challenge: EmailVerificationChallenge, grant: string}
     */
    public function issueGrant(string $email, string $purpose): array
    {
        abort_unless($this->enabled(), 403, 'Development email verification bypass is not enabled.');

        return DB::transaction(function () use ($email, $purpose): array {
            $email = Str::lower(trim($email));

            EmailVerificationChallenge::query()
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'updated_at' => now()]);

            $grant = bin2hex(random_bytes(32));
            $grantExpiresAt = now()->addMinutes((int) config('verification.email.grant_expires_minutes', 10));
            $challenge = EmailVerificationChallenge::create([
                'email' => $email,
                'purpose' => $purpose,
                'code_hash' => Hash::make(bin2hex(random_bytes(16))),
                'max_attempts' => 0,
                'expires_at' => $grantExpiresAt,
                'resend_available_at' => now(),
                'verified_at' => now(),
                'verification_grant_hash' => hash('sha256', $grant),
                'verification_grant_expires_at' => $grantExpiresAt,
            ]);

            return ['challenge' => $challenge, 'grant' => $grant];
        });
    }
}
