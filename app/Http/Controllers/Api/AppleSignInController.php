<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\AppleSignInService;
use App\Services\Auth\SocialLoginService;
use App\Services\SingleLoginSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppleSignInController extends Controller
{
    public function authenticate(Request $request, AppleSignInService $apple, SocialLoginService $socialLogin): JsonResponse
    {
        $data = $request->validate([
            'identity_token' => 'required|string',
            'authorization_code' => 'nullable|string',
            'name' => 'nullable|string|max:150',
            'device_name' => 'nullable|string|max:100',
            'nonce' => 'nullable|string|max:255',
        ]);

        $claims = $apple->verifyIdentityToken($data['identity_token'], $data['nonce'] ?? null);
        [$user, $needsOnboarding, $active] = $socialLogin->resolve(
            'apple', (string) $claims['sub'], isset($claims['email']) ? (string) $claims['email'] : null,
            $data['name'] ?? null,
            null, null, null, null,
            ['is_private_email' => filter_var($claims['is_private_email'] ?? false, FILTER_VALIDATE_BOOLEAN)],
        );

        abort_unless($active, 403, 'This account is disabled. Please contact support or an administrator.');
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'data' => $user->loadAccessContext(),
            'token' => app(SingleLoginSessionService::class)->issueApiToken($user, $data['device_name'] ?? 'apple-native'),
            'onboarding_required' => $needsOnboarding,
        ]);
    }
}
