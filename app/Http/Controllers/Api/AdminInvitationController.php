<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AccessInvitationMail;
use App\Models\EducatorInvite;
use App\Models\InstituteInvite;
use App\Models\InstituteUser;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\AdminInvitationTokenService;
use App\Services\RoleAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminInvitationController extends Controller
{
    public function index(): JsonResponse
    {
        $educators = EducatorInvite::query()->whereNull('accepted_at')->with('institute:id,name')->latest()->get()
            ->map(fn (EducatorInvite $invite) => $this->serializeEducator($invite));
        $institutes = InstituteInvite::query()->whereNull('accepted_at')->with('institute:id,name')->latest()->get()
            ->map(fn (InstituteInvite $invite) => $this->serializeInstitute($invite));
        $parents = ParentStudentLink::query()->where('status', 'pending')->whereNotNull('invite_token_hash')
            ->with(['parent:id,name,email', 'student:id,name,email'])->latest()->get()
            ->map(fn (ParentStudentLink $link) => $this->serializeParent($link));

        return response()->json(['data' => $educators->concat($institutes)->concat($parents)
            ->sortByDesc('created_at')->values()]);
    }

    public function activate(Request $request, string $type, int $id): JsonResponse
    {
        $this->assertType($type);
        $actorId = $request->user()->id;

        $data = DB::transaction(function () use ($type, $id, $actorId): array {
            if ($type === 'educator') {
                $invite = EducatorInvite::query()->whereKey($id)->whereNull('accepted_at')->lockForUpdate()->firstOrFail();
                $user = User::query()->where('email', $invite->email)->lockForUpdate()->first();
                $user = $user ?? User::create(['name' => $this->defaultName($invite->email), 'email' => $invite->email, 'status' => 'active']);
                if ($user->status->value !== 'active') $user->update(['status' => 'active']);
                app(RoleAssignmentService::class)->assign($user, $invite->role_slug, 'system', null, $actorId);
                if ($invite->institute_id) {
                    InstituteUser::updateOrCreate(
                        ['institute_id' => $invite->institute_id, 'user_id' => $user->id],
                        ['status' => 'active', 'joined_at' => now(), 'invited_by' => $invite->invited_by],
                    );
                }
                $invite->update(['accepted_at' => now()]);
                app(AdminInvitationTokenService::class)->forget($type, $id);
                return ['user_id' => $user->id, 'type' => $type, 'id' => $id];
            }

            if ($type === 'institute') {
                $invite = InstituteInvite::query()->whereKey($id)->whereNull('accepted_at')->lockForUpdate()->firstOrFail();
                $user = User::query()->where('email', $invite->email)->lockForUpdate()->first();
                $user = $user ?? User::create(['name' => $this->defaultName($invite->email), 'email' => $invite->email, 'status' => 'active']);
                if ($user->status->value !== 'active') $user->update(['status' => 'active']);
                InstituteUser::updateOrCreate(
                    ['institute_id' => $invite->institute_id, 'user_id' => $user->id],
                    ['status' => 'active', 'joined_at' => now(), 'invited_by' => $invite->invited_by],
                );
                app(RoleAssignmentService::class)->assign($user, $invite->role_slug, 'institute', $invite->institute_id, $actorId);
                $invite->update(['accepted_at' => now()]);
                app(AdminInvitationTokenService::class)->forget($type, $id);
                return ['user_id' => $user->id, 'type' => $type, 'id' => $id];
            }

            $link = ParentStudentLink::query()->whereKey($id)->where('status', 'pending')->lockForUpdate()->firstOrFail();
            $parent = User::query()->whereKey($link->parent_user_id)->lockForUpdate()->firstOrFail();
            if ($parent->status->value !== 'active') $parent->update(['status' => 'active']);
            app(RoleAssignmentService::class)->assign($parent, 'parent', 'system', null, $actorId);
            $link->update([
                'status' => 'active',
                'accepted_at' => now(),
                'invite_token_hash' => null,
                'invite_expires_at' => null,
            ]);
            app(AdminInvitationTokenService::class)->forget($type, $id);
            return ['user_id' => $parent->id, 'student_user_id' => $link->student_user_id, 'type' => $type, 'id' => $id];
        });

        return response()->json(['data' => $data, 'message' => 'Invitation activated manually.']);
    }

    public function link(string $type, int $id): JsonResponse
    {
        $this->assertType($type);
        [$token, $path] = $this->tokenAndPath($type, $id, false);
        return response()->json(['data' => ['activation_path' => $path, 'activation_url' => rtrim(config('app.url'), '/').$path, 'invite_token' => $token]]);
    }

    public function resend(string $type, int $id): JsonResponse
    {
        $this->assertType($type);
        [$token, $path, $email, $heading] = $this->tokenAndPath($type, $id, false, true);
        Mail::to($email)->queue(new AccessInvitationMail($heading, rtrim(config('app.url'), '/').$path));
        return response()->json(['data' => ['activation_path' => $path, 'activation_url' => rtrim(config('app.url'), '/').$path, 'invite_token' => $token], 'message' => 'Invitation resent.']);
    }

    public function extend(Request $request, string $type, int $id): JsonResponse
    {
        $this->assertType($type);
        $data = $request->validate(['days' => ['nullable', 'integer', 'min:1', 'max:30']]);
        $days = (int) ($data['days'] ?? 7);
        $expiresAt = now()->addDays($days);

        if ($type === 'educator') EducatorInvite::query()->whereKey($id)->whereNull('accepted_at')->firstOrFail()->update(['expires_at' => $expiresAt]);
        elseif ($type === 'institute') InstituteInvite::query()->whereKey($id)->whereNull('accepted_at')->firstOrFail()->update(['expires_at' => $expiresAt]);
        else ParentStudentLink::query()->whereKey($id)->where('status', 'pending')->firstOrFail()->update(['invite_expires_at' => $expiresAt]);

        return response()->json(['data' => ['expires_at' => $expiresAt->toIso8601String()], 'message' => "Invitation extended by {$days} days."]);
    }

    public function revoke(string $type, int $id): JsonResponse
    {
        $this->assertType($type);
        DB::transaction(function () use ($type, $id): void {
            if ($type === 'educator') {
                EducatorInvite::query()->whereKey($id)->whereNull('accepted_at')->firstOrFail()->delete();
            } elseif ($type === 'institute') {
                InstituteInvite::query()->whereKey($id)->whereNull('accepted_at')->firstOrFail()->delete();
            } else {
                $link = ParentStudentLink::query()->whereKey($id)->where('status', 'pending')->firstOrFail();
                $link->delete();
            }
            app(AdminInvitationTokenService::class)->forget($type, $id);
        });

        return response()->json([], 204);
    }

    private function tokenAndPath(string $type, int $id, bool $rotate = false, bool $includeMail = false): array
    {
        $tokens = app(AdminInvitationTokenService::class);
        $token = $rotate ? null : $tokens->retrieve($type, $id);

        if ($type === 'educator') {
            $invite = EducatorInvite::query()->whereKey($id)->whereNull('accepted_at')->firstOrFail();
            if (! $token || hash('sha256', $token) !== $invite->token_hash) {
                $token = Str::random(64); $invite->update(['token_hash' => hash('sha256', $token)]); $tokens->remember($type, $id, $token);
            }
            $path = '/educator/activate?token='.$token;
            return $includeMail ? [$token, $path, $invite->email, ucfirst($invite->role_slug).' invitation'] : [$token, $path];
        }

        if ($type === 'institute') {
            $invite = InstituteInvite::query()->whereKey($id)->whereNull('accepted_at')->with('institute')->firstOrFail();
            if (! $token || hash('sha256', $token) !== $invite->token_hash) {
                $token = Str::random(64); $invite->update(['token_hash' => hash('sha256', $token)]); $tokens->remember($type, $id, $token);
            }
            $path = '/institute/activate?token='.$token;
            $heading = ucfirst(str_replace('_', ' ', $invite->role_slug)).' invitation'.($invite->institute?->name ? ' for '.$invite->institute->name : '');
            return $includeMail ? [$token, $path, $invite->email, $heading] : [$token, $path];
        }

        $link = ParentStudentLink::query()->whereKey($id)->where('status', 'pending')->with(['parent', 'student'])->firstOrFail();
        if (! $token || hash('sha256', $token) !== $link->invite_token_hash) {
            $token = Str::random(64); $link->update(['invite_token_hash' => hash('sha256', $token)]); $tokens->remember($type, $id, $token);
        }
        $path = '/parent/activate?token='.$token;
        $heading = 'Parent access invitation for '.($link->student?->name ?? 'student');
        return $includeMail ? [$token, $path, $link->parent?->email, $heading] : [$token, $path];
    }

    private function serializeEducator(EducatorInvite $invite): array
    {
        return ['id' => $invite->id, 'type' => 'educator', 'email' => $invite->email, 'role' => $invite->role_slug, 'context' => $invite->institute?->name ?: 'Platform/global', 'expires_at' => $invite->expires_at?->toIso8601String(), 'created_at' => $invite->created_at?->toIso8601String(), 'expired' => $invite->expires_at?->isPast() ?? true];
    }

    private function serializeInstitute(InstituteInvite $invite): array
    {
        return ['id' => $invite->id, 'type' => 'institute', 'email' => $invite->email, 'role' => $invite->role_slug, 'context' => $invite->institute?->name ?: 'Institute', 'expires_at' => $invite->expires_at?->toIso8601String(), 'created_at' => $invite->created_at?->toIso8601String(), 'expired' => $invite->expires_at?->isPast() ?? true];
    }

    private function serializeParent(ParentStudentLink $link): array
    {
        return ['id' => $link->id, 'type' => 'parent', 'email' => $link->parent?->email ?? '', 'role' => 'parent', 'context' => 'Student: '.($link->student?->name ?? '#'.$link->student_user_id), 'expires_at' => $link->invite_expires_at?->toIso8601String(), 'created_at' => $link->created_at?->toIso8601String(), 'expired' => ! $link->invite_expires_at || $link->invite_expires_at->isPast()];
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['educator', 'institute', 'parent'], true)) {
            throw ValidationException::withMessages(['type' => 'Unsupported invitation type.']);
        }
    }

    private function defaultName(string $email): string
    {
        return Str::headline(Str::before($email, '@')) ?: 'Invited User';
    }
}
