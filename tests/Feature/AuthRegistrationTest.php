<?php

namespace Tests\Feature;

use App\Mail\StudentSignupVerificationMail;
use App\Models\Course;
use App\Models\ExamBoard;
use App\Models\Role;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_keeps_user_credentials_out_of_student_profiles(): void
    {
        [$course, $board, $subject] = $this->registrationFixture();
        Mail::fake();
        config()->set('verification.email.development_bypass', true);

        $grant = $this->postJson('/api/v1/auth/register/email-verification', [
            'email' => 'Student@Example.Test',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'student@example.test')
            ->assertJsonPath('data.development_bypass', true)
            ->json('data.verification_grant');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'verification_grant' => $grant,
            'course_id' => $course->id,
            'exam_board_id' => $board->id,
            'school_year' => 'Year 10',
            'school_name' => 'Test School',
            'subject_ids' => [$subject->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test Student')
            ->assertJsonPath('data.student_profile.school_year', 'Year 10');

        $this->assertDatabaseHas('users', [
            'name' => 'Test Student',
            'email' => 'student@example.test',
        ]);
        $this->assertNotNull($response->json('data.email_verified_at'));
        Mail::assertNothingSent();
        $this->assertDatabaseHas('student_profiles', [
            'ownership_type' => 'platform',
            'school_year' => 'Year 10',
            'school_name' => 'Test School',
        ]);
    }

    public function test_registration_is_rejected_without_a_verified_email_grant(): void
    {
        [$course, $board, $subject] = $this->registrationFixture();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Unverified Student',
            'email' => 'unverified@example.test',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'course_id' => $course->id,
            'exam_board_id' => $board->id,
            'subject_ids' => [$subject->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('verification_grant');

        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.test']);
    }

    public function test_signup_code_verification_creates_a_verified_account(): void
    {
        [$course, $board, $subject] = $this->registrationFixture();
        Mail::fake();
        config()->set('verification.email.development_bypass', false);
        config()->set('verification.email.mode', 'static');
        config()->set('verification.email.static_code', '654321');

        $challenge = $this->postJson('/api/v1/auth/register/email-verification', [
            'email' => 'verified@example.test',
        ])->assertCreated()->assertJsonPath('data.development_bypass', false);

        Mail::assertSent(StudentSignupVerificationMail::class);

        $grant = $this->postJson('/api/v1/auth/register/email-verification/'.$challenge->json('data.id').'/verify', [
            'code' => '654321',
        ])->assertOk()->json('data.verification_grant');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Verified Student',
            'email' => 'verified@example.test',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'verification_grant' => $grant,
            'course_id' => $course->id,
            'exam_board_id' => $board->id,
            'subject_ids' => [$subject->id],
        ])->assertCreated()->assertJsonPath('data.email', 'verified@example.test');

        $this->assertDatabaseMissing('users', ['email' => 'verified@example.test', 'email_verified_at' => null]);
    }

    private function registrationFixture(): array
    {
        Role::firstOrCreate(['slug' => 'student'], [
            'name' => 'Student',
            'scope' => 'system',
            'is_system' => true,
        ]);
        $board = ExamBoard::create(['name' => 'Cambridge', 'code' => 'CAIE-REGISTER-'.uniqid(), 'status' => 'active', 'metadata' => []]);
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHY-REGISTER-'.uniqid(), 'status' => 'active', 'metadata' => []]);
        $course = Course::create(['exam_board_id' => $board->id, 'name' => 'O Level', 'code' => 'OL-REGISTER-'.uniqid(), 'level' => 'O Level', 'qualification_family' => 'O Level', 'status' => 'active', 'metadata' => []]);
        $course->subjects()->sync([$subject->id]);

        return [$course, $board, $subject];
    }
}
