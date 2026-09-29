<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AppleSignInService;
use App\Services\Auth\SocialLoginService;
use App\Services\SingleLoginSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class AppleAuthController extends Controller
{
    public function redirect(AppleSignInService $apple): RedirectResponse
    {
        $nonce = $apple->newNonce();
        $state = Crypt::encryptString(json_encode(['nonce' => $nonce, 'issued_at' => time()], JSON_THROW_ON_ERROR));
        return redirect()->away($apple->authorizationUrl($state, $nonce));
    }

    public function callback(Request $request, AppleSignInService $apple, SocialLoginService $socialLogin): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect('/login?apple_error='.urlencode((string) $request->input('error')));
        }

        $request->validate(['state' => 'required|string', 'code' => 'required|string', 'id_token' => 'required|string', 'user' => 'nullable|string']);

        try {
            $state = json_decode(Crypt::decryptString((string) $request->input('state')), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($state) || empty($state['nonce']) || time() - (int) ($state['issued_at'] ?? 0) > 600) {
                throw new RuntimeException('Apple sign-in state has expired.');
            }

            $callbackClaims = $apple->verifyIdentityToken((string) $request->input('id_token'), (string) $state['nonce'], [(string) config('services.apple_sign_in.client_id')]);
            $tokens = $apple->exchangeCode((string) $request->input('code'));
            $claims = $apple->verifyIdentityToken((string) $tokens['id_token'], null, [(string) config('services.apple_sign_in.client_id')]);
            if (! hash_equals((string) $callbackClaims['sub'], (string) $claims['sub'])) {
                throw new RuntimeException('Apple authorization code and identity token do not belong to the same user.');
            }
            $appleUser = $this->userPayload((string) $request->input('user', ''));
            $name = trim(($appleUser['name']['firstName'] ?? '').' '.($appleUser['name']['lastName'] ?? '')) ?: null;
            $email = $claims['email'] ?? ($appleUser['email'] ?? null);

            [$user, $needsOnboarding, $active] = $socialLogin->resolve(
                'apple', (string) $claims['sub'], is_string($email) ? $email : null, $name,
                null, $tokens['access_token'] ?? null, $tokens['refresh_token'] ?? null,
                isset($tokens['expires_in']) ? (int) $tokens['expires_in'] : null,
                ['is_private_email' => filter_var($claims['is_private_email'] ?? false, FILTER_VALIDATE_BOOLEAN)],
            );

            abort_unless($active, 403, 'This account is disabled. Please contact support or an administrator.');
            app(SingleLoginSessionService::class)->prepareWebLogin($user);
            Auth::login($user, true);
            $user->update(['last_login_at' => now()]);

            return redirect('/auth/apple/complete?onboarding='.($needsOnboarding ? '1' : '0'));
        } catch (\Throwable $e) {
            report($e);
            return redirect('/login?apple_error='.urlencode('Apple sign-in could not be completed.'));
        }
    }

    /** @return array<string,mixed> */
    private function userPayload(string $raw): array
    {
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
