<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use App\Services\SingleLoginSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $g = Socialite::driver('google')->user();
        $u = DB::transaction(function () use ($g) {
            $u = User::firstOrCreate(['email' => $g->getEmail()], ['name' => $g->getName() ?: 'Student', 'password' => Str::random(40), 'email_verified_at' => now()]);
            SocialAccount::updateOrCreate(['provider' => 'google', 'provider_user_id' => $g->getId()], ['user_id' => $u->id, 'provider_email' => $g->getEmail(), 'avatar_url' => $g->getAvatar(), 'access_token' => $g->token, 'refresh_token' => $g->refreshToken, 'token_expires_at' => $g->expiresIn ? now()->addSeconds($g->expiresIn) : null]);
            if (!$u->studentProfile()->exists()) {
                app(RoleAssignmentService::class)->assign($u, 'student');
                app(StudentOwnershipService::class)->createPlatformStudent($u);
            }
            return $u;
        });
        app(SingleLoginSessionService::class)->prepareWebLogin($u);
        Auth::login($u, true);
        return redirect('/dashboard');
    }
}
