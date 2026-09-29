<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Course;
use App\Models\ExamBoard;
use App\Models\User;
use App\Models\LearningSession;
use App\Models\EducatorAccessGrant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentCatalogAndTutorFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_managed_topics_are_returned_by_the_student_catalog(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $this->assignRole($admin, 'system_admin');
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHY', 'status' => 'active']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/topics', [
            'subject_id' => $subject->id,
            'name' => 'Mechanics',
            'code' => 'MECHANICS',
            'status' => 'active',
            'display_order' => 1,
            'metadata' => [],
        ])->assertCreated();

        $this->getJson('/api/v1/catalog')
            ->assertOk()
            ->assertJsonPath('data.topics.0.name', 'Mechanics')
            ->assertJsonPath('data.topics.0.subject_id', $subject->id);
    }

    public function test_catalog_shows_active_and_coming_soon_records_but_hides_inactive_records(): void
    {
        $activeBoard = ExamBoard::create(['name' => 'Active Board', 'code' => 'ACTIVE', 'status' => 'active', 'metadata' => []]);
        ExamBoard::create(['name' => 'Coming Board', 'code' => 'SOON', 'status' => 'coming_soon', 'metadata' => []]);
        ExamBoard::create(['name' => 'Hidden Board', 'code' => 'HIDDEN', 'status' => 'inactive', 'metadata' => []]);
        Course::create(['exam_board_id' => $activeBoard->id, 'name' => 'Coming Course', 'code' => 'COMING-COURSE', 'level' => 'O Level', 'qualification_family' => 'O Level', 'status' => 'coming_soon', 'metadata' => []]);
        Subject::create(['name' => 'Coming Subject', 'code' => 'COMING-SUBJECT', 'status' => 'coming_soon', 'metadata' => []]);
        Subject::create(['name' => 'Hidden Subject', 'code' => 'HIDDEN-SUBJECT', 'status' => 'inactive', 'metadata' => []]);

        $response = $this->getJson('/api/v1/catalog')->assertOk();

        $response->assertJsonFragment(['name' => 'Active Board', 'status' => 'active']);
        $response->assertJsonFragment(['name' => 'Coming Board', 'status' => 'coming_soon']);
        $response->assertJsonFragment(['name' => 'Coming Course', 'status' => 'coming_soon']);
        $response->assertJsonFragment(['name' => 'Coming Subject', 'status' => 'coming_soon']);
        $response->assertJsonMissing(['name' => 'Hidden Board']);
        $response->assertJsonMissing(['name' => 'Hidden Subject']);
    }

    public function test_coming_soon_catalog_records_cannot_be_selected_through_account_api(): void
    {
        $user = User::factory()->create();
        $board = ExamBoard::create(['name' => 'Coming Board', 'code' => 'SOON-SELECT', 'status' => 'coming_soon', 'metadata' => []]);
        $course = Course::create(['exam_board_id' => $board->id, 'name' => 'Coming Course', 'code' => 'SOON-SELECT', 'level' => 'O Level', 'qualification_family' => 'O Level', 'status' => 'coming_soon', 'metadata' => []]);
        $subject = Subject::create(['name' => 'Coming Subject', 'code' => 'SOON-SELECT', 'status' => 'coming_soon', 'metadata' => []]);

        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/account', [
            'exam_board_id' => $board->id,
            'course_id' => $course->id,
            'subject_ids' => [$subject->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['exam_board_id', 'course_id', 'subject_ids.0']);
    }

    public function test_only_tutors_can_create_and_update_question_feedback(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $tutor = User::factory()->create();
        $student = User::factory()->create();
        $this->assignRole($tutor, 'tutor');
        $this->assignRole($student, 'student');
        $subject = Subject::firstOrCreate(['code' => 'NEWTON-FEEDBACK'], ['name' => 'Physics', 'status' => 'active']);
        EducatorAccessGrant::create(['educator_user_id' => $tutor->id, 'scope_type' => 'subject', 'scope_id' => $subject->id, 'status' => 'active', 'granted_by' => $tutor->id]);
        LearningSession::create(['user_id' => $student->id, 'session_uid' => 'learn-session-1', 'session_type' => 'learn', 'subject' => 'Physics', 'earned' => 0, 'total_marks' => 1, 'accuracy' => 0, 'time_seconds' => 1, 'average_seconds_per_question' => 1, 'questions' => [['q' => 'State Newton’s first law.']], 'metadata' => ['question_source' => 'ai'], 'status' => 'completed', 'completed_at' => now()]);
        $payload = [
            'session_type' => 'learn',
            'session_id' => 'learn-session-1',
            'question_key' => 'result-0',
            'question_text' => 'State Newton’s first law.',
            'answer_text' => 'A body remains at rest.',
            'feedback' => 'Add the resultant-force condition.',
            'verdict' => 'partially_correct',
        ];

        $this->actingAs($student, 'sanctum')->postJson('/api/v1/tutor-question-feedback', $payload)->assertForbidden();
        $this->actingAs($tutor, 'sanctum')->postJson('/api/v1/tutor-question-feedback', $payload)->assertCreated();
        $this->actingAs($tutor, 'sanctum')->postJson('/api/v1/tutor-question-feedback', [...$payload, 'feedback' => 'Updated feedback.'])->assertOk();
        $this->actingAs($tutor, 'sanctum')->getJson('/api/v1/tutor-question-feedback?session_type=learn&session_id=learn-session-1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.feedback', 'Updated feedback.');
    }

    private function assignRole(User $user, string $slug): void
    {
        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => DB::table('roles')->where('slug', $slug)->value('id'),
            'context_type' => 'system',
            'context_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
