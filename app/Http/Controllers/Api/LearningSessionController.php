<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningSession;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Services\EducatorAccessService;
use App\Services\StudentProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearningSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('student') || $request->user()->hasRole('teacher') || $request->user()->hasRole('tutor') || $request->user()->hasSystemRole('system_admin', 'system_staff'), 403);
        $data = $request->validate(['user_id' => ['nullable', 'integer', 'exists:users,id']]);
        $userId = (int) ($data['user_id'] ?? $request->user()->id);
        abort_unless($request->user()->canReviewStudent($userId), 403);

        return response()->json(['data' => LearningSession::query()
            ->where('user_id', $userId)
            ->with('user:id,name')
            ->latest('completed_at')
            ->limit(100)
            ->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('student') || $request->user()->hasRole('teacher') || $request->user()->hasRole('tutor'), 403);
        $data = $request->validate([
            'session_uid' => ['required', 'string', 'max:100'],
            'session_type' => ['required', Rule::in(['learn', 'exam'])],
            'level' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:180'],
            'topic' => ['nullable', 'string', 'max:240'],
            'earned' => ['required', 'integer', 'min:0'],
            'total_marks' => ['required', 'integer', 'min:0'],
            'accuracy' => ['required', 'integer', 'between:0,100'],
            'grade' => ['nullable', 'string', 'max:20'],
            'time_seconds' => ['required', 'integer', 'min:0'],
            'average_seconds_per_question' => ['required', 'integer', 'min:0'],
            'questions' => ['required', 'array', 'max:100'],
            'questions.*.q' => ['required', 'string', 'max:20000'],
            'questions.*.type' => ['required', Rule::in(['mcq', 'short'])],
            'questions.*.marks' => ['required', 'integer', 'min:0'],
            'questions.*.marks_awarded' => ['required', 'integer', 'min:0'],
            'questions.*.correct' => ['required', 'boolean'],
            'questions.*.skipped' => ['required', 'boolean'],
            'questions.*.given' => ['nullable', 'string', 'max:30000'],
            'questions.*.star_answer' => ['nullable', 'string', 'max:30000'],
            'questions.*.time_seconds' => ['required', 'integer', 'min:0'],
            'metadata' => ['sometimes', 'array'],
            'completed_at' => ['required', 'date'],
        ]);

        $existing = LearningSession::query()->where(['session_uid' => $data['session_uid'], 'user_id' => $request->user()->id])->first();
        $session = LearningSession::updateOrCreate(
            ['session_uid' => $data['session_uid'], 'user_id' => $request->user()->id],
            [...$data, 'user_id' => $request->user()->id, 'status' => 'completed', 'started_at' => $existing?->started_at ?? $data['completed_at']],
        );

        return response()->json(['data' => $session->load('user:id,name')], $session->wasRecentlyCreated ? 201 : 200);
    }

    public function teacherStudents(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('teacher'), 403);
        $studentIds = app(EducatorAccessService::class)->studentIds($request->user());
        $progress = app(StudentProgressService::class);
        $access = app(EducatorAccessService::class);

        return response()->json(['data' => StudentProfile::query()
            ->whereIn('user_id', $studentIds)
            ->where('status', 'active')
            ->with(['user:id,name,email', 'course:id,name,level', 'examBoard:id,name', 'subjects:id,name'])
            ->orderBy('id')
            ->get()
            ->map(function (StudentProfile $profile) use ($progress, $access, $request): array {
                return [
                    ...$profile->toArray(),
                    'can_manage_parents' => $access->canManageParentsForStudent($request->user(), $profile->user_id),
                    'progress' => $progress->summary($profile->user_id),
                ];
            })]);
    }

    public function progress(Request $request, string $sessionUid): JsonResponse
    {
        abort_unless($request->user()->hasRole('student') || $request->user()->hasRole('teacher') || $request->user()->hasRole('tutor'), 403);
        $data = $request->validate([
            'session_type' => ['required', Rule::in(['learn', 'exam'])],
            'level' => ['nullable', 'string', 'max:80'], 'subject' => ['required', 'string', 'max:180'],
            'topic' => ['nullable', 'string', 'max:240'], 'time_seconds' => ['required', 'integer', 'min:0'],
            'questions' => ['sometimes', 'array', 'max:100'], 'metadata' => ['sometimes', 'array'],
            'started_at' => ['nullable', 'date'],
        ]);
        $session = LearningSession::updateOrCreate(
            ['session_uid' => $sessionUid, 'user_id' => $request->user()->id],
            [...$data, 'status' => 'active', 'earned' => 0, 'total_marks' => 0, 'accuracy' => 0, 'grade' => null,
                'average_seconds_per_question' => 0, 'completed_at' => null, 'started_at' => $data['started_at'] ?? now()],
        );
        return response()->json(['data' => $session], $session->wasRecentlyCreated ? 201 : 200);
    }

    public function tutorReviewQueue(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('tutor'), 403);
        $subjectNames = Subject::query()->whereIn('id', app(EducatorAccessService::class)->subjectIds($request->user()))->pluck('name');
        $items = LearningSession::query()->where('status', 'completed')->whereIn('subject', $subjectNames)->latest('completed_at')->limit(100)->get()
            ->filter(function (LearningSession $session) use ($subjectNames): bool {
                $metadata = $session->metadata ?? [];
                return $subjectNames->contains($session->subject)
                    && in_array('ai', [$metadata['question_source'] ?? null, $metadata['answer_source'] ?? null, $metadata['scoring_source'] ?? null], true);
            })
            ->flatMap(function (LearningSession $session) {
                return collect($session->questions ?? [])->map(function (array $question, int $index) use ($session): array {
                    return [
                        'session_id' => $session->session_uid,
                        'session_type' => $session->session_type,
                        'question_key' => 'result-'.$index,
                        'subject' => $session->subject,
                        'topic' => $session->topic,
                        'question_text' => $question['q'] ?? '',
                        'submitted_answer' => $question['given'] ?? null,
                        'ai_answer' => $question['star_answer'] ?? null,
                        'marks_awarded' => $question['marks_awarded'] ?? 0,
                        'marks' => $question['marks'] ?? 0,
                        'completed_at' => $session->completed_at,
                    ];
                });
            })->values();

        return response()->json(['data' => $items]);
    }

    public function tutorAcademicAccess(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('tutor'), 403);
        return response()->json(['data' => [
            'subject_ids' => app(EducatorAccessService::class)->subjectIds($request->user()),
        ]]);
    }
}
