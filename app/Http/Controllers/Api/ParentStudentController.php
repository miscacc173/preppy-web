<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Models\Subscription;
use App\Services\RoleAssignmentService;
use App\Services\StudentProgressService;
use App\Services\EducatorAccessService;
use App\Services\SingleLoginSessionService;
use App\Services\SubscriptionEntitlementService;
use App\Mail\AccessInvitationMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ParentStudentController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        abort_unless($r->user()->hasRole('parent'), 403);
        $links = ParentStudentLink::query()->where('parent_user_id', $r->user()->id)
            ->with(['student.studentProfile.course', 'student.studentProfile.examBoard', 'student.studentProfile.subjects', 'student.studentProfile.institute'])
            ->latest()->get();

        return response()->json(['data' => $links->map(function (ParentStudentLink $link): array {
            $active = $link->status === 'active';
            $profile = $active ? $link->student?->studentProfile : null;
            $planSubjects = $active && $link->student
                ? app(SubscriptionEntitlementService::class)->activePlanSubjectsFor($link->student)
                : collect();
            $planSubjectNames = $planSubjects->pluck('name')->values();
            $subscription = $active && $link->can_view_subscription
                ? Subscription::query()->where(['subscriber_type' => 'student', 'subscriber_id' => $link->student_user_id])->with('plan')->latest()->first()
                : null;
            $progress = $active && $link->can_view_performance
                ? app(StudentProgressService::class)->summary($link->student_user_id, $planSubjectNames->all())
                : null;

            return [
                'id' => $link->id,
                'status' => $link->status,
                'relationship' => $link->relationship,
                'permissions' => ['performance' => $link->can_view_performance, 'subscription' => $link->can_view_subscription],
                'student' => [
                    'id' => $link->student_user_id,
                    'name' => $link->student?->name,
                    'school_year' => $profile?->school_year,
                    'school_name' => $profile?->school_name,
                    'course' => $profile?->course?->name,
                    'exam_board' => $profile?->examBoard?->name,
                    'institute' => $profile?->institute?->name,
                    'subjects' => $planSubjectNames->all(),
                ],
                'performance' => $progress,
                'subscription' => $subscription ? [
                    'status' => $subscription->status->value,
                    'plan' => $subscription->plan?->name,
                    'period_end' => $subscription->current_period_ends_at?->toIso8601String(),
                ] : null,
            ];
        })->values()]);
    }

    public function store(Request $r, User $student): JsonResponse
    {
        $profile = $student->studentProfile;
        abort_unless($profile && $this->canManageLink($r->user(), $student, $profile->institute_id), 403);
        $d = $r->validate(['parent_email' => 'required|email', 'parent_name' => 'nullable|string|max:150', 'relationship' => 'nullable|string|max:60', 'can_view_subscription' => 'boolean']);
        $p = User::firstOrCreate(['email' => $d['parent_email']], ['name' => $d['parent_name'] ?? 'Parent']);
        app(RoleAssignmentService::class)->assign($p, 'parent');
        $token = Str::random(64);
        $l = ParentStudentLink::updateOrCreate(['parent_user_id' => $p->id, 'student_user_id' => $student->id], ['relationship' => $d['relationship'] ?? null, 'status' => 'pending', 'can_view_performance' => true, 'can_view_subscription' => $d['can_view_subscription'] ?? false, 'invited_by' => $r->user()->id, 'accepted_at' => null, 'invite_token_hash' => hash('sha256', $token), 'invite_expires_at' => now()->addDays(7)]);
        app(\App\Services\AdminInvitationTokenService::class)->remember('parent', $l->id, $token);
        $path = '/parent/activate?token='.$token;
        Mail::to($p->email)->queue(new AccessInvitationMail('Parent access invitation for '.$student->name, rtrim(config('app.url'), '/').$path));
        return response()->json(['data' => $l, 'invite_token' => $token, 'activation_path' => $path], 201);
    }

    public function showInvite(string $token): JsonResponse
    {
        $link = $this->pendingInvite($token)->load(['parent', 'student']);
        return response()->json(['data' => ['parent_name' => $link->parent?->name, 'email' => $link->parent?->email, 'student_name' => $link->student?->name, 'relationship' => $link->relationship, 'existing_account' => filled($link->parent?->getRawOriginal('password')), 'expires_at' => $link->invite_expires_at?->toIso8601String()]]);
    }

    public function activateInvite(Request $request, string $token): JsonResponse
    {
        $link = $this->pendingInvite($token);
        $parent = User::findOrFail($link->parent_user_id);
        abort_unless($parent->status->value === 'active', 403, 'This account is not active.');
        $existingAccount = filled($parent->getRawOriginal('password'));
        $data = $request->validate($existingAccount
            ? ['current_password' => 'required|string']
            : ['name' => 'required|string|max:150', 'password' => 'required|string|min:8|confirmed']);
        if ($existingAccount) {
            abort_unless(Hash::check($data['current_password'], $parent->getRawOriginal('password')), 422, 'Invalid credentials');
        }
        $user = DB::transaction(function () use ($link, $data): User {
            $user = User::findOrFail($link->parent_user_id);
            if (! filled($user->getRawOriginal('password'))) {
                $user->update(['name' => $data['name'], 'password' => $data['password'], 'email_verified_at' => $user->email_verified_at ?? now()]);
            }
            $link->update(['status' => 'active', 'accepted_at' => now(), 'invite_token_hash' => null, 'invite_expires_at' => null]);
            app(\App\Services\AdminInvitationTokenService::class)->forget('parent', $link->id);
            return $user;
        });
        return response()->json([
            'data' => $user->loadAccessContext(),
            'token' => app(SingleLoginSessionService::class)->issueApiToken($user, 'parent-web'),
        ]);
    }

    public function accept(Request $r, ParentStudentLink $link): JsonResponse
    {
        abort_unless($link->parent_user_id === $r->user()->id, 403);
        $link->update(['status' => 'active', 'accepted_at' => now()]);
        return response()->json(['data' => $link]);
    }

    public function destroy(ParentStudentLink $link): JsonResponse
    {
        $student = User::find($link->student_user_id)?->studentProfile;
        $studentUser = User::findOrFail($link->student_user_id);
        abort_unless($student && $this->canManageLink(request()->user(), $studentUser, $student->institute_id), 403);
        $link->delete();
        return response()->json([], 204);
    }

    public function forStudent(Request $request, User $student): JsonResponse
    {
        $profile = $student->studentProfile;
        abort_unless($profile && $this->canManageLink($request->user(), $student, $profile->institute_id), 403);

        return response()->json(['data' => ParentStudentLink::query()
            ->where('student_user_id', $student->id)
            ->with('parent:id,name,email,status')
            ->latest()
            ->get()]);
    }

    private function pendingInvite(string $token): ParentStudentLink
    {
        $link = ParentStudentLink::query()->where('invite_token_hash', hash('sha256', $token))->where('status', 'pending')->firstOrFail();
        abort_if(! $link->invite_expires_at || $link->invite_expires_at->isPast(), 422, 'Parent invite expired.');
        return $link;
    }

    private function canManageLink(User $actor, User $student, ?int $instituteId): bool
    {
        return $actor->hasSystemRole('system_admin')
            || ($instituteId && $actor->hasRole('institute_admin', 'institute', $instituteId))
            || app(EducatorAccessService::class)->canManageParentsForStudent($actor, $student->id);
    }
}
