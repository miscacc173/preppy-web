<?php

namespace App\Services;

use App\Models\GuestDemoSession;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GuestDemoAccessService
{
    public function createAccessToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashAccessToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function authorize(Request $request, GuestDemoSession $session): void
    {
        $token = trim((string) $request->header('X-Guest-Demo-Token'));
        $expectedHash = (string) $session->access_token_hash;

        if ($token === '' || $expectedHash === '' || ! hash_equals($expectedHash, $this->hashAccessToken($token))) {
            abort(401, 'Invalid guest demo access token.');
        }

        if ($session->expires_at->isPast()) {
            throw ValidationException::withMessages(['session' => 'Guest demo session has expired.']);
        }
    }
}
