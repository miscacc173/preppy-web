<?php

namespace App\Services;

use App\Models\LearningSession;
use App\Models\StudentProfile;

class StudentProgressService
{
    /**
     * @param array<int, string>|null $allowedSubjects Null keeps the original
     * profile/session scope; an array restricts every metric to those subjects.
     */
    public function summary(int $studentUserId, ?array $allowedSubjects = null): array
    {
        LearningSession::query()->where('user_id', $studentUserId)->where('status', 'active')->where('updated_at', '<', now()->subHours(12))->update(['status' => 'abandoned']);
        $sessions = LearningSession::query()->where('user_id', $studentUserId)->latest('started_at')->limit(200)->get();
        $allowed = $allowedSubjects === null ? null : collect($allowedSubjects)
            ->filter(fn ($subject) => is_string($subject) && trim($subject) !== '')
            ->mapWithKeys(fn (string $subject) => [mb_strtolower(trim($subject)) => trim($subject)]);
        if ($allowed !== null) {
            $sessions = $sessions->filter(fn (LearningSession $session) => $allowed->has(mb_strtolower(trim((string) $session->subject))))->values();
        }
        $completed = $sessions->where('status', 'completed');
        $active = $sessions->where('status', 'active')->values();

        $daily = $completed->groupBy(fn (LearningSession $session) => ($session->completed_at ?? $session->created_at)->toDateString())
            ->map(fn ($items, $date) => [
                'date' => $date,
                'sessions' => $items->count(),
                'minutes' => (int) ceil($items->sum('time_seconds') / 60),
                'average_score' => (int) round($items->avg('accuracy') ?? 0),
            ])->sortByDesc('date')->take(30)->values();

        if ($allowed !== null) {
            $subjectNames = $allowed->values();
        } else {
            $profileSubjects = StudentProfile::query()
                ->where('user_id', $studentUserId)
                ->with('subjects:id,name')
                ->first()?->subjects?->pluck('name') ?? collect();
            $subjectNames = $profileSubjects
                ->merge($sessions->pluck('subject'))
                ->filter()
                ->unique()
                ->values();
        }

        $subjects = $subjectNames->map(function (string $subject) use ($sessions): array {
            $normalizedSubject = mb_strtolower(trim($subject));
            $subjectSessions = $sessions
                ->filter(fn (LearningSession $session) => mb_strtolower(trim((string) $session->subject)) === $normalizedSubject)
                ->values();
            $completedSessions = $subjectSessions->where('status', 'completed')->values();
            $activeSessions = $subjectSessions->where('status', 'active')->values();
            $latestCompleted = $completedSessions->first();

            return [
                'subject' => $subject,
                'readiness' => (int) round($completedSessions->avg('accuracy') ?? 0),
                'has_activity' => $completedSessions->isNotEmpty(),
                'earned' => (int) $completedSessions->sum('earned'),
                'total_marks' => (int) $completedSessions->sum('total_marks'),
                'completed_sessions' => $completedSessions->count(),
                'active_sessions' => $activeSessions->count(),
                'average_score' => $completedSessions->isEmpty() ? null : (int) round($completedSessions->avg('accuracy')),
                'best_score' => $completedSessions->isEmpty() ? null : (int) $completedSessions->max('accuracy'),
                'latest_score' => $latestCompleted ? (int) $latestCompleted->accuracy : null,
                'total_minutes' => (int) ceil($completedSessions->sum('time_seconds') / 60),
                'last_session_at' => $latestCompleted?->completed_at?->toIso8601String(),
                'recent_sessions' => $subjectSessions->take(12)->map(fn (LearningSession $session) => $this->session($session))->values(),
            ];
        })->sortByDesc(fn (array $subject) => $subject['last_session_at'] ?? '')->values();

        return [
            'daily_progress' => $daily,
            'active_sessions' => $active->map(fn (LearningSession $session) => $this->session($session))->values(),
            'completed_sessions' => $completed->take(50)->map(fn (LearningSession $session) => $this->session($session))->values(),
            'subject_readiness' => $subjects,
            'overall_readiness' => $completed->isEmpty() ? null : (int) round($completed->avg('accuracy')),
        ];
    }

    private function session(LearningSession $session): array
    {
        return [
            'id' => $session->session_uid,
            'type' => $session->session_type,
            'subject' => $session->subject,
            'topic' => $session->topic,
            'status' => $session->status,
            'score' => $session->accuracy,
            'earned' => $session->earned,
            'total_marks' => $session->total_marks,
            'grade' => $session->grade,
            'time_seconds' => $session->time_seconds,
            'average_seconds_per_question' => $session->average_seconds_per_question,
            'question_count' => count($session->questions ?? []),
            'answered_count' => collect($session->questions ?? [])->where('skipped', false)->count(),
            'correct_count' => collect($session->questions ?? [])->where('correct', true)->count(),
            'started_at' => $session->started_at?->toIso8601String(),
            'completed_at' => $session->completed_at?->toIso8601String(),
        ];
    }
}
