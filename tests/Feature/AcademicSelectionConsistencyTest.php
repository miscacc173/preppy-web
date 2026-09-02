<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ExamBoard;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentOwnershipService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicSelectionConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_returns_only_the_boards_and_subjects_assigned_to_each_course(): void
    {
        [$course, $allowedBoard, $allowedSubject] = $this->catalogFixture();

        $response = $this->getJson('/api/v1/catalog')->assertOk();
        $courseJson = collect($response->json('data.courses'))->firstWhere('id', $course->id);

        $this->assertSame([$allowedBoard->id], collect($courseJson['exam_boards'])->pluck('id')->all());
        $this->assertSame([$allowedSubject->id], collect($courseJson['subjects'])->pluck('id')->all());
    }

    public function test_signup_rejects_a_board_or_subject_not_allowed_by_the_course(): void
    {
        $this->seed(RolePermissionSeeder::class);
        [$course, , , $otherBoard, $otherSubject] = $this->catalogFixture();
        config()->set('verification.email.development_bypass', true);
        $grant = $this->postJson('/api/v1/auth/register/email-verification', [
            'email' => 'invalid-selection@example.com',
        ])->assertCreated()->json('data.verification_grant');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Invalid Student', 'email' => 'invalid-selection@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'verification_grant' => $grant,
            'course_id' => $course->id, 'exam_board_id' => $otherBoard->id, 'subject_ids' => [$otherSubject->id],
        ])->assertUnprocessable()->assertJsonValidationErrors(['exam_board_id', 'subject_ids']);
    }

    public function test_learning_path_account_update_accepts_the_same_valid_selection(): void
    {
        [$course, $board, $subject] = $this->catalogFixture();
        $user = User::factory()->create();
        app(StudentOwnershipService::class)->createPlatformStudent($user);

        $this->actingAs($user, 'sanctum')->patchJson('/api/v1/account', [
            'course_id' => $course->id, 'exam_board_id' => $board->id, 'subject_ids' => [$subject->id],
        ])->assertOk()->assertJsonPath('data.student_profile.course_id', $course->id);

        $this->assertDatabaseHas('student_subjects', ['student_profile_id' => $user->studentProfile->id, 'subject_id' => $subject->id]);
    }

    private function catalogFixture(): array
    {
        $allowedBoard = ExamBoard::create(['name' => 'Cambridge', 'code' => 'CAIE-SELECT', 'status' => 'active', 'metadata' => []]);
        $otherBoard = ExamBoard::create(['name' => 'Other Board', 'code' => 'OTHER-SELECT', 'status' => 'active', 'metadata' => []]);
        $allowedSubject = Subject::create(['name' => 'Physics', 'code' => 'PHY-SELECT', 'status' => 'active', 'metadata' => []]);
        $otherSubject = Subject::create(['name' => 'History', 'code' => 'HIS-SELECT', 'status' => 'active', 'metadata' => []]);
        $course = Course::create(['exam_board_id' => $allowedBoard->id, 'name' => 'O Level', 'code' => 'OL-SELECT', 'level' => 'O Level', 'qualification_family' => 'O Level', 'status' => 'active', 'metadata' => []]);
        $course->subjects()->sync([$allowedSubject->id]);
        return [$course, $allowedBoard, $allowedSubject, $otherBoard, $otherSubject];
    }
}
