<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AccessInvitationMail;
use App\Models\EducatorInvite;
use App\Models\InstituteUser;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\SingleLoginSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EducatorInviteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'role_slug' => ['required', Rule::in(['teacher', 'tutor'])], 'institute_id' => ['nullable', 'integer', 'exists:institutes,id']]);
        $system = $request->user()->hasSystemRole('system_admin', 'system_staff');
        if (! $system) {
            abort_unless($data['role_slug'] === 'teacher' && ! empty($data['institute_id']) && $request->user()->hasRole('institute_admin', 'institute', (int) $data['institute_id']), 403);
        }
        $token = Str::random(64);
        $invite = EducatorInvite::create([...$data, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(7), 'invited_by' => $request->user()->id]);
        app(\App\Services\AdminInvitationTokenService::class)->remember('educator', $invite->id, $token);
        $path = '/educator/activate?token='.$token;
        Mail::to($data['email'])->queue(new AccessInvitationMail(ucfirst($data['role_slug']).' invitation', rtrim(config('app.url'), '/').$path));
        return response()->json(['data' => $invite, 'invite_token' => $token, 'activation_path' => $path], 201);
    }

    public function show(string $token): JsonResponse
    {
        $invite = $this->pending($token)->load('institute');
        $existing = User::query()->where('email', $invite->email)->whereNotNull('password')->exists();
        return response()->json(['data' => ['email' => $invite->email, 'role' => $invite->role_slug, 'institute' => $invite->institute?->name, 'existing_account' => $existing, 'expires_at' => $invite->expires_at->toIso8601String()]]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invite = $this->pending($token);
        $existing = User::query()->where('email', $invite->email)->first();
        abort_if($existing && $existing->status->value !== 'active', 403, 'This account is not active.');
        $hasPassword = filled($existing?->getRawOriginal('password'));
        $data = $request->validate($hasPassword ? ['current_password' => ['required', 'string']] : ['name' => ['required', 'string', 'max:150'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        if ($hasPassword) abort_unless(Hash::check($data['current_password'], $existing->getRawOriginal('password')), 422, 'Invalid credentials');

        $user = DB::transaction(function () use ($invite, $existing, $hasPassword, $data): User {
            $user = $existing ?? new User(['email' => $invite->email]);
            if (! $hasPassword) $user->fill(['name' => $data['name'], 'password' => $data['password'], 'email_verified_at' => now()]);
            $user->save();
            app(RoleAssignmentService::class)->assign($user, $invite->role_slug, 'system', null, $invite->invited_by);
            if ($invite->institute_id) InstituteUser::updateOrCreate(['institute_id' => $invite->institute_id, 'user_id' => $user->id], ['status' => 'active', 'joined_at' => now(), 'invited_by' => $invite->invited_by]);
            $invite->update(['accepted_at' => now()]);
            app(\App\Services\AdminInvitationTokenService::class)->forget('educator', $invite->id);
            return $user;
        });
        return response()->json([
            'data' => $user->loadAccessContext(),
            'token' => app(SingleLoginSessionService::class)->issueApiToken($user, $invite->role_slug.'-web'),
        ]);
    }

    private function pending(string $token): EducatorInvite
    {
        $invite = EducatorInvite::query()->where('token_hash', hash('sha256', $token))->whereNull('accepted_at')->firstOrFail();
        abort_if($invite->expires_at->isPast(), 422, 'Invite expired');
        return $invite;
    }
}
