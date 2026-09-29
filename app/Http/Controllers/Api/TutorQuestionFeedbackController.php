<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TutorQuestionFeedback;
use App\Models\LearningSession;
use App\Models\Subject;
use App\Services\EducatorAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TutorQuestionFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeTutor($request);
        $data = $request->validate([
            'session_type' => ['required', Rule::in(['learn', 'exam'])],
            'session_id' => ['required', 'string', 'max:100'],
        ]);
        $this->authorizeSession($request, $data['session_id']);

        return response()->json(['data' => TutorQuestionFeedback::query()
            ->where('tutor_user_id', $request->user()->id)
            ->where($data)
            ->orderBy('id')
            ->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeTutor($request);
        $data = $this->validated($request);
        $this->authorizeSession($request, $data['session_id'], $data['question_key'], $data['question_text']);
        $feedback = TutorQuestionFeedback::updateOrCreate([
            'tutor_user_id' => $request->user()->id,
            'session_type' => $data['session_type'],
            'session_id' => $data['session_id'],
            'question_key' => $data['question_key'],
        ], [...$data, 'tutor_user_id' => $request->user()->id]);

        return response()->json(['data' => $feedback], $feedback->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, TutorQuestionFeedback $feedback): JsonResponse
    {
        $this->authorizeTutor($request);
        abort_unless($feedback->tutor_user_id === $request->user()->id, 403);
        $feedback->delete();
        return response()->json(status: 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'session_type' => ['required', Rule::in(['learn', 'exam'])],
            'session_id' => ['required', 'string', 'max:100'],
            'question_key' => ['required', 'string', 'max:190'],
            'question_text' => ['required', 'string', 'max:10000'],
            'answer_text' => ['nullable', 'string', 'max:20000'],
            'feedback' => ['required', 'string', 'max:10000'],
            'verdict' => ['nullable', Rule::in(['correct', 'partially_correct', 'incorrect', 'needs_review'])],
            'metadata' => ['sometimes', 'array'],
        ]);
    }

    private function authorizeTutor(Request $request): void
    {
        abort_unless($request->user()->hasRole('tutor'), 403);
    }

    private function authorizeSession(Request $request, string $sessionUid, ?string $questionKey = null, ?string $questionText = null): void
    {
        $session = LearningSession::query()->where('session_uid', $sessionUid)->first();
        abort_unless($session, 404);
        $metadata = $session->metadata ?? [];
        $ownTutorSession = (int) $session->user_id === (int) $request->user()->id;
        abort_unless($ownTutorSession || in_array('ai', [$metadata['question_source'] ?? null, $metadata['answer_source'] ?? null, $metadata['scoring_source'] ?? null], true), 403);
        $allowedSubjects = Subject::query()->whereIn('id', app(EducatorAccessService::class)->subjectIds($request->user()))->pluck('name');
        abort_unless($allowedSubjects->contains($session->subject), 403);
        if ($questionKey !== null) {
            abort_unless(preg_match('/^(?:result|learn|exam)-(\d+)(?::(?:question|answer))?$/', $questionKey, $matches) === 1, 422, 'The question reference is invalid.');
            $question = ($session->questions ?? [])[(int) $matches[1]] ?? null;
            abort_unless($question && ($question['q'] ?? null) === $questionText, 422, 'The question does not belong to this session.');
        }
    }
}
