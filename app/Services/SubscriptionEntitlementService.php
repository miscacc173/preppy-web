<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;

class SubscriptionEntitlementService
{
    public function currentFor(User $user): ?Subscription
    {
        return Subscription::query()->where(['subscriber_type' => 'student', 'subscriber_id' => $user->id])
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->with(['plan', 'items'])->latest('id')->first();
    }

    public function allows(User $user, string $feature = 'learning', ?int $subjectId = null, ?string $subjectName = null): bool
    {
        if (! $user->hasRole('student')) return true;
        if ($feature === 'dashboard') return $this->hasActivePlan($user);
        $subscription = $this->currentFor($user);
        if (! $subscription || ! $this->statusAllows($subscription)) return false;
        $item = $subscription->items->first();
        if (! $item) return false;
        $entitlements = $item->entitlements ?? [];
        $features = $entitlements['features'] ?? [];
        if (is_array($features) && $features !== []) {
            $normalized = array_is_list($features) ? array_fill_keys($features, true) : $features;
            $aliases = $feature === 'learning' ? ['learning', 'practice_mode', 'learn', 'exam'] : [$feature];
            if (! collect($aliases)->contains(fn ($key) => ($normalized[$key] ?? false) === true)) return false;
        }
        $scope = $entitlements['scope'] ?? 'all_subjects';
        if (in_array($scope, ['all_subjects', 'institute'], true) || (! $subjectId && ! $subjectName)) return true;
        if (! $subjectId && $subjectName) $subjectId = Subject::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($subjectName)])->value('id');
        if (! $subjectId) return false;
        if (in_array($scope, ['subject', 'multi_subject'], true)) return in_array($subjectId, array_map('intval', $entitlements['subject_ids'] ?? []), true);
        if ($scope === 'course') {
            return (int) ($entitlements['course_id'] ?? 0) === (int) $user->studentProfile?->course_id;
        }
        return false;
    }

    public function hasActivePlan(User $user): bool
    {
        if (! $user->hasRole('student')) return true;

        $subscription = Subscription::query()
            ->where(['subscriber_type' => 'student', 'subscriber_id' => $user->id, 'status' => 'active'])
            ->with('items')
            ->latest('id')
            ->first();

        return $subscription !== null
            && $subscription->items->isNotEmpty()
            && (! $subscription->current_period_ends_at || $subscription->current_period_ends_at->isFuture());
    }

    /** @return Collection<int, Subject> */
    public function activePlanSubjectsFor(User $user): Collection
    {
        if (! $user->hasRole('student')) return collect();

        $subscription = Subscription::query()
            ->where(['subscriber_type' => 'student', 'subscriber_id' => $user->id, 'status' => 'active'])
            ->with(['plan', 'items'])
            ->latest('id')
            ->first();

        if (! $subscription || ! $this->statusAllows($subscription)) return collect();

        $item = $subscription->items->first();
        if (! $item) return collect();

        $entitlements = $item->entitlements ?? [];
        $scope = (string) ($entitlements['scope'] ?? $subscription->plan?->scope?->value ?? 'all_subjects');

        if (in_array($scope, ['subject', 'multi_subject'], true)) {
            $subjectIds = collect($entitlements['subject_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($subjectIds->isEmpty()) {
                $subjectIds = $subscription->plan?->subjects()->pluck('subjects.id') ?? collect();
            }

            return Subject::query()->whereIn('id', $subjectIds)->orderBy('name')->get();
        }

        if ($scope === 'course') {
            $courseId = (int) ($entitlements['course_id'] ?? $subscription->plan?->course_id ?? $user->studentProfile?->course_id ?? 0);
            if (! $courseId) return collect();

            return Course::query()->find($courseId)?->subjects()->orderBy('subjects.name')->get() ?? collect();
        }

        $profileSubjects = $user->studentProfile?->subjects()->orderBy('subjects.name')->get() ?? collect();
        if ($profileSubjects->isNotEmpty()) return $profileSubjects;

        $courseId = (int) ($user->studentProfile?->course_id ?? 0);
        if ($courseId) {
            return Course::query()->find($courseId)?->subjects()->orderBy('subjects.name')->get() ?? collect();
        }

        return collect();
    }

    public function statusAllows(Subscription $subscription): bool
    {
        return match ($subscription->status->value) {
            'active' => ! $subscription->current_period_ends_at || $subscription->current_period_ends_at->isFuture(),
            'trialing' => $subscription->trial_ends_at?->isFuture() === true,
            'past_due' => config('billing.allow_past_due_during_grace', true) && $subscription->grace_ends_at?->isFuture() === true,
            default => false,
        };
    }

    /** @return array<string, mixed> */
    public function summary(User $user, string $feature = 'learning'): array
    {
        $subscription = $this->currentFor($user);
        return [
            'allowed' => $this->allows($user, $feature),
            'feature' => $feature,
            'subscription_id' => $subscription?->id,
            'status' => $subscription?->status->value ?? 'none',
            'grace_ends_at' => $subscription?->grace_ends_at?->toIso8601String(),
            'period_ends_at' => $subscription?->current_period_ends_at?->toIso8601String(),
        ];
    }
}
