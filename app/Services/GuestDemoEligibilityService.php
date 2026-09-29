<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestDemoEligibilityService
{
    public function assertAvailable(string $email): void
    {
        if (DB::table('guest_demo_email_claims')->where('email', $this->normalize($email))->exists()) {
            $this->alreadyUsed();
        }
    }

    public function claim(string $email): string
    {
        $normalized = $this->normalize($email);

        try {
            DB::table('guest_demo_email_claims')->insert([
                'email' => $normalized,
                'claimed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() !== '23505') {
                throw $exception;
            }
            $this->alreadyUsed();
        }

        return $normalized;
    }

    public function bindSession(string $email, string $sessionId): void
    {
        DB::table('guest_demo_email_claims')
            ->where('email', $this->normalize($email))
            ->update(['guest_demo_session_id' => $sessionId, 'updated_at' => now()]);
    }

    private function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function alreadyUsed(): never
    {
        throw ValidationException::withMessages([
            'email' => 'A guest session has already been used for this email. Please sign up or sign in to continue.',
        ]);
    }
}
