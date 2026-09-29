<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteUser;
use App\Models\User;
use App\Models\Subject;
use App\Models\EducatorAccessGrant;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\EnsureSubscriptionEntitlement;
use Tests\TestCase;

class TutorLearningSessionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureSubscriptionEntitlement::class);
    }

    public function test_teacher_can_view_sessions_for_students_in_the_same_institute(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $institute = Institute::create(['name' => 'North School', 'slug' => 'north-school', 'code' => 'NORTH', 'status' => 'active', 'settings' => []]);
        $student = User::factory()->create();
        $teacher = User::factory()->create();
        app(RoleAssignmentService::class)->assign($student, 'student');
        app(RoleAssignmentService::class)->assign($teacher, 'teacher');
        app(StudentOwnershipService::class)->createInstituteStudent($student, $institute, ['status' => 'active']);
        InstituteUser::create(['institute_id' => $institute->id, 'user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        EducatorAccessGrant::create(['educator_user_id' => $teacher->id, 'institute_id' => $institute->id, 'scope_type' => 'student', 'scope_id' => $student->id, 'status' => 'active', 'granted_by' => $teacher->id]);

        $this->actingAs($student, 'sanctum')->postJson('/api/v1/learning-sessions', $this->sessionPayload())
            ->assertCreated()->assertJsonPath('data.session_uid', 'session-physics-1');

        $this->actingAs($teacher, 'sanctum')->getJson('/api/v1/teacher/students')
            ->assertOk()->assertJsonPath('data.0.user_id', $student->id);
        $this->getJson("/api/v1/learning-sessions?user_id={$student->id}")
            ->assertOk()->assertJsonPath('data.0.subject', 'Physics');
    }

    public function test_teacher_cannot_view_a_student_outside_their_institute(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = User::factory()->create();
        $teacher = User::factory()->create();
        app(RoleAssignmentService::class)->assign($student, 'student');
        app(RoleAssignmentService::class)->assign($teacher, 'teacher');
        app(StudentOwnershipService::class)->createPlatformStudent($student, ['status' => 'active']);

        $this->actingAs($student, 'sanctum')->postJson('/api/v1/learning-sessions', $this->sessionPayload())->assertCreated();
        $this->actingAs($teacher, 'sanctum')->getJson("/api/v1/learning-sessions?user_id={$student->id}")->assertForbidden();
    }

    public function test_tutor_only_receives_ai_review_items_and_can_submit_feedback(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = User::factory()->create();
        $tutor = User::factory()->create();
        app(RoleAssignmentService::class)->assign($student, 'student');
        app(RoleAssignmentService::class)->assign($tutor, 'tutor');
        app(StudentOwnershipService::class)->createPlatformStudent($student, ['status' => 'active']);
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHYSICS-REVIEW', 'status' => 'active']);
        EducatorAccessGrant::create(['educator_user_id' => $tutor->id, 'scope_type' => 'subject', 'scope_id' => $subject->id, 'status' => 'active', 'granted_by' => $tutor->id]);
        $this->actingAs($student, 'sanctum')->postJson('/api/v1/learning-sessions', $this->sessionPayload())->assertCreated();

        $this->actingAs($tutor, 'sanctum')->getJson('/api/v1/tutor/review-queue')
            ->assertOk()->assertJsonPath('data.0.question_text', 'Define force.');
        $this->getJson("/api/v1/learning-sessions?user_id={$student->id}")->assertForbidden();
        $this->postJson('/api/v1/tutor-question-feedback', [
            'session_type' => 'learn', 'session_id' => 'session-physics-1', 'question_key' => 'result-0',
            'question_text' => 'Define force.', 'answer_text' => 'Force equals mass multiplied by acceleration.',
            'feedback' => 'The AI answer is acceptable.', 'verdict' => 'correct', 'metadata' => ['scoring_snapshot' => '2/2'],
        ])->assertCreated();
    }

    public function test_tutor_can_use_learn_and_exam_modes_and_leave_separate_question_and_answer_feedback(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $tutor = User::factory()->create();
        app(RoleAssignmentService::class)->assign($tutor, 'tutor');
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHYSICS-TUTOR-MODES', 'status' => 'active']);
        EducatorAccessGrant::create(['educator_user_id' => $tutor->id, 'scope_type' => 'subject', 'scope_id' => $subject->id, 'status' => 'active', 'granted_by' => $tutor->id]);

        $this->actingAs($tutor, 'sanctum')->getJson('/api/v1/tutor/academic-access')
            ->assertOk()->assertJsonPath('data.subject_ids.0', $subject->id);

        foreach (['learn', 'exam'] as $mode) {
            $sessionId = "tutor-{$mode}-1";
            $this->putJson("/api/v1/learning-sessions/{$sessionId}/progress", [
                'session_type' => $mode,
                'level' => 'O Level',
                'subject' => 'Physics',
                'topic' => 'Forces',
                'time_seconds' => 5,
                'questions' => [['q' => 'Define force.', 'model' => 'Force equals mass multiplied by acceleration.']],
                'metadata' => ['question_source' => 'past_papers'],
            ])->assertCreated()->assertJsonPath('data.session_type', $mode);

            foreach (['question', 'answer'] as $kind) {
                $this->postJson('/api/v1/tutor-question-feedback', [
                    'session_type' => $mode,
                    'session_id' => $sessionId,
                    'question_key' => "result-0:{$kind}",
                    'question_text' => 'Define force.',
                    'answer_text' => $kind === 'answer' ? 'Force equals mass multiplied by acceleration.' : null,
                    'feedback' => "Tutor {$kind} feedback.",
                    'verdict' => 'correct',
                    'metadata' => ['feedback_type' => $kind],
                ])->assertCreated()->assertJsonPath('data.metadata.feedback_type', $kind);
            }

            $this->getJson("/api/v1/tutor-question-feedback?session_type={$mode}&session_id={$sessionId}")
                ->assertOk()->assertJsonCount(2, 'data');
        }

        $this->getJson('/api/v1/learning-sessions')
            ->assertOk()->assertJsonCount(2, 'data');
    }

    private function sessionPayload(): array
    {
        return [
            'session_uid' => 'session-physics-1', 'session_type' => 'learn', 'level' => 'O Level',
            'subject' => 'Physics', 'topic' => 'Forces', 'earned' => 2, 'total_marks' => 2,
            'accuracy' => 100, 'grade' => 'A*', 'time_seconds' => 60, 'average_seconds_per_question' => 60,
            'completed_at' => now()->toIso8601String(),
            'metadata' => ['question_source' => 'ai'],
            'questions' => [[
                'q' => 'Define force.', 'type' => 'short', 'marks' => 2, 'marks_awarded' => 2,
                'correct' => true, 'skipped' => false, 'given' => 'Mass times acceleration.',
                'star_answer' => 'Force equals mass multiplied by acceleration.', 'time_seconds' => 60,
            ]],
        ];
    }
}
