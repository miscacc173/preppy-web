<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialLoginService;
use App\Services\SingleLoginSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(SocialLoginService $socialLogin): RedirectResponse
    {
        $google = Socialite::driver('google')->user();
        [$user, $needsOnboarding, $active] = $socialLogin->resolve(
            'google', (string) $google->getId(), $google->getEmail(), $google->getName(), $google->getAvatar(),
            $google->token, $google->refreshToken, $google->expiresIn ? (int) $google->expiresIn : null,
        );

        abort_unless($active, 403, 'This account is disabled. Please contact support or an administrator.');
        app(SingleLoginSessionService::class)->prepareWebLogin($user);
        Auth::login($user, true);
        $user->update(['last_login_at' => now()]);

        return redirect('/auth/google/complete?onboarding='.($needsOnboarding ? '1' : '0'));
    }
}
