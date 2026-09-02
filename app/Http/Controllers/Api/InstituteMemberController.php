<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\InstituteInvite;
use App\Models\InstituteUser;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\SingleLoginSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstituteMemberController extends Controller
{
    public function index(Institute $institute): JsonResponse
    {
        $this->authorizeInstitute($institute, false);
        return response()->json(InstituteUser::where('institute_id', $institute->id)->with('user.roles')->paginate());
    }

    public function invite(Request $r, Institute $institute): JsonResponse
    {
        $this->authorizeInstitute($institute, true);
        $d = $r->validate(['email' => 'required|email', 'role_slug' => 'required|in:institute_admin,institute_staff', 'expires_in_days' => 'nullable|integer|min:1|max:30']);
        $token = Str::random(64);
        $i = InstituteInvite::create(['institute_id' => $institute->id, 'email' => $d['email'], 'role_slug' => $d['role_slug'], 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays($d['expires_in_days'] ?? 7), 'invited_by' => $r->user()->id]);
        app(\App\Services\AdminInvitationTokenService::class)->remember('institute', $i->id, $token);
        return response()->json(['data' => $i, 'invite_token' => $token], 201);
    }

    public function accept(Request $r): JsonResponse
    {
        $d = $r->validate(['token' => 'required|string']);
        $i = InstituteInvite::where('token_hash', hash('sha256', $d['token']))->whereNull('accepted_at')->firstOrFail();
        abort_if($i->expires_at->isPast(), 422, 'Invite expired');
        $existingUser = User::query()->where('email', $i->email)->first();
        abort_if($existingUser && $existingUser->status->value !== 'active', 403, 'This account is not active.');
        $existingAccount = filled($existingUser?->getRawOriginal('password'));
        $credentials = $r->validate($existingAccount
            ? ['current_password' => 'required|string']
            : ['name' => 'required|string|max:150', 'password' => 'required|string|min:8|confirmed']);
        if ($existingAccount) {
            abort_unless(Hash::check($credentials['current_password'], $existingUser->getRawOriginal('password')), 422, 'Invalid credentials');
        }
        $d = array_merge($d, $credentials);
        $u = DB::transaction(function () use ($i, $d) {
            $u = User::firstOrNew(['email' => $i->email]);
            if (! filled($u->getRawOriginal('password'))) {
                $u->fill(['name' => $d['name'], 'password' => $d['password'], 'email_verified_at' => $u->email_verified_at ?? now()]);
            }
            $u->save();
            InstituteUser::updateOrCreate(['institute_id' => $i->institute_id, 'user_id' => $u->id], ['status' => 'active', 'joined_at' => now(), 'invited_by' => $i->invited_by]);
            app(RoleAssignmentService::class)->assign($u, $i->role_slug, 'institute', $i->institute_id, $i->invited_by);
            $i->update(['accepted_at' => now()]);
            app(\App\Services\AdminInvitationTokenService::class)->forget('institute', $i->id);
            return $u;
        });
        return response()->json([
            'data' => $u->loadAccessContext(),
            'token' => app(SingleLoginSessionService::class)->issueApiToken($u, 'institute-web'),
        ]);
    }

    public function showInvite(string $token): JsonResponse
    {
        $invite = InstituteInvite::query()->where('token_hash', hash('sha256', $token))->whereNull('accepted_at')->with('institute')->firstOrFail();
        abort_if($invite->expires_at->isPast(), 422, 'Invite expired');
        $existingAccount = User::query()->where('email', $invite->email)->whereNotNull('password')->exists();
        return response()->json(['data' => ['email' => $invite->email, 'role' => $invite->role_slug, 'institute' => $invite->institute?->name, 'existing_account' => $existingAccount, 'expires_at' => $invite->expires_at->toIso8601String()]]);
    }

    public function destroy(Institute $institute, User $user): JsonResponse
    {
        $this->authorizeInstitute($institute, true);
        InstituteUser::where(['institute_id' => $institute->id, 'user_id' => $user->id])->delete();
        DB::table('user_roles')->where(['user_id' => $user->id, 'context_type' => 'institute', 'context_id' => $institute->id])->delete();
        return response()->json([], 204);
    }

    public function update(Request $request, Institute $institute, User $user): JsonResponse
    {
        $this->authorizeInstitute($institute, true);
        $data = $request->validate([
            'position' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:pending,active,suspended'],
            'role_slug' => ['nullable', 'in:institute_admin,institute_staff,teacher'],
        ]);
        $member = InstituteUser::where(['institute_id' => $institute->id, 'user_id' => $user->id])->firstOrFail();
        $member->update(['position' => $data['position'] ?? null, 'status' => $data['status']]);
        if (! empty($data['role_slug'])) {
            DB::table('user_roles')->where(['user_id' => $user->id, 'context_type' => 'institute', 'context_id' => $institute->id])->delete();
            if ($data['role_slug'] === 'teacher') {
                app(RoleAssignmentService::class)->assign($user, 'teacher', 'system', null, $request->user()->id);
            } else {
                app(RoleAssignmentService::class)->assign($user, $data['role_slug'], 'institute', $institute->id, $request->user()->id);
            }
        }
        return response()->json(['data' => $member->fresh()->load('user')]);
    }

    private function authorizeInstitute(Institute $institute, bool $manage): void
    {
        $user = request()->user();
        abort_unless($user->canAccessInstitute($institute->id), 403);
        if ($manage && ! $user->hasSystemRole('system_admin', 'system_staff')) {
            abort_unless($user->hasRole('institute_admin', 'institute', $institute->id), 403);
        }
    }
}
