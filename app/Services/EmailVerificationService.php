<?php

namespace App\Services;

use App\Mail\GuestDemoVerificationMail;
use App\Mail\StudentSignupVerificationMail;
use App\Models\EmailVerificationChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EmailVerificationService
{
    public const PURPOSE_GUEST_DEMO = 'guest_demo';
    public const PURPOSE_STUDENT_SIGNUP = 'student_signup';

    public function requestChallenge(string $email, string $purpose): EmailVerificationChallenge
    {
        $email = $this->normalizeEmail($email);

        $latest = EmailVerificationChallenge::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        if ($latest?->resend_available_at?->isFuture()) {
            throw ValidationException::withMessages([
                'email' => 'Please wait before requesting another verification code.',
            ]);
        }

        EmailVerificationChallenge::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now(), 'updated_at' => now()]);

        $code = $this->generateCode();
        $challenge = EmailVerificationChallenge::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'max_attempts' => (int) config('verification.email.max_attempts', 5),
            'expires_at' => now()->addMinutes((int) config('verification.email.expires_minutes', 10)),
            'resend_available_at' => now()->addSeconds((int) config('verification.email.resend_cooldown_seconds', 60)),
        ]);

        try {
            $mail = match ($purpose) {
                self::PURPOSE_GUEST_DEMO => new GuestDemoVerificationMail($code),
                self::PURPOSE_STUDENT_SIGNUP => new StudentSignupVerificationMail($code),
                default => throw new RuntimeException('Unsupported email verification purpose.'),
            };
            Mail::to($email)->send($mail);
        } catch (\Throwable $exception) {
            $challenge->delete();
            throw $exception;
        }

        return $challenge;
    }

    /**
     * @return array{challenge: EmailVerificationChallenge, grant: string}
     */
    public function verify(EmailVerificationChallenge $challenge, string $code, string $purpose): array
    {
        $result = DB::transaction(function () use ($challenge, $code, $purpose): array {
            $locked = EmailVerificationChallenge::query()->lockForUpdate()->findOrFail($challenge->id);

            if ($locked->purpose !== $purpose) {
                return ['error' => 'This verification request is not valid for the guest demo.'];
            }
            if ($locked->consumed_at) {
                return ['error' => 'This verification request has already been used.'];
            }
            if ($locked->expires_at->isPast()) {
                return ['error' => 'The verification code has expired. Request a new code.'];
            }
            if ($locked->verified_at) {
                return ['error' => 'This verification code has already been verified.'];
            }
            if ($locked->attempts >= $locked->max_attempts) {
                return ['error' => 'Too many verification attempts. Request a new code.'];
            }

            $locked->increment('attempts');
            $locked->refresh();

            if (! Hash::check($code, $locked->code_hash)) {
                return ['error' => 'The verification code is incorrect.'];
            }

            $grant = bin2hex(random_bytes(32));
            $locked->update([
                'verified_at' => now(),
                'verification_grant_hash' => hash('sha256', $grant),
                'verification_grant_expires_at' => now()->addMinutes((int) config('verification.email.grant_expires_minutes', 10)),
            ]);

            return ['challenge' => $locked->fresh(), 'grant' => $grant];
        });

        if (isset($result['error'])) {
            throw ValidationException::withMessages(['code' => $result['error']]);
        }

        /** @var array{challenge: EmailVerificationChallenge, grant: string} $result */
        return $result;
    }

    public function consumeGrant(string $grant, string $purpose): EmailVerificationChallenge
    {
        return DB::transaction(function () use ($grant, $purpose): EmailVerificationChallenge {
            $hash = hash('sha256', $grant);
            $challenge = EmailVerificationChallenge::query()
                ->where('verification_grant_hash', $hash)
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->purpose !== $purpose || ! $challenge->verified_at) {
                throw ValidationException::withMessages(['verification_grant' => 'Email verification is required before starting the demo.']);
            }
            if ($challenge->consumed_at) {
                throw ValidationException::withMessages(['verification_grant' => 'This email verification has already been used.']);
            }
            if (! $challenge->verification_grant_expires_at || $challenge->verification_grant_expires_at->isPast()) {
                throw ValidationException::withMessages(['verification_grant' => 'Email verification has expired. Verify your email again.']);
            }

            $challenge->update(['consumed_at' => now()]);

            return $challenge->fresh();
        });
    }

    private function generateCode(): string
    {
        $mode = (string) config('verification.email.mode', 'random');

        if ($mode === 'static') {
            if (app()->environment('production')) {
                throw new RuntimeException('Static email verification codes are not permitted in production.');
            }

            $staticCode = trim((string) config('verification.email.static_code'));
            if (! preg_match('/^\d{6}$/', $staticCode)) {
                throw new RuntimeException('EMAIL_VERIFICATION_STATIC_CODE must be a 6-digit code when static verification mode is enabled.');
            }

            return $staticCode;
        }

        $length = (int) config('verification.email.code_length', 6);
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
