<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ExamBoard;
use App\Models\Subject;
use App\Services\DevelopmentEmailVerificationBypassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestDemoSingleSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_email_can_start_only_one_guest_session_regardless_of_case(): void
    {
        Mail::fake();
        config()->set('verification.email.mode', 'static');
        config()->set('verification.email.static_code', '123456');
        $board = ExamBoard::create(['name' => 'Cambridge', 'code' => 'CAIE-GUEST', 'status' => 'active', 'metadata' => []]);
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHY-GUEST', 'status' => 'active', 'metadata' => []]);
        $course = Course::create(['exam_board_id' => $board->id, 'name' => 'O Level', 'code' => 'OL-GUEST', 'level' => 'O Level', 'qualification_family' => 'O Level', 'status' => 'active', 'metadata' => []]);
        $course->subjects()->sync([$subject->id]);

        $challenge = $this->postJson('/api/v1/guest-demos/email-verification', [
            'email' => 'Single.Guest@Example.com',
        ])->assertCreated()->assertJsonPath('data.email', 'single.guest@example.com');

        $verified = $this->postJson('/api/v1/guest-demos/email-verification/'.$challenge->json('data.id').'/verify', [
            'code' => '123456',
        ])->assertOk();

        $session = $this->postJson('/api/v1/guest-demos', [
            'verification_grant' => $verified->json('data.verification_grant'),
            'course_id' => $course->id,
            'exam_board_id' => $board->id,
            'subject_ids' => [$subject->id],
        ])->assertCreated()->assertJsonPath('data.email', 'single.guest@example.com');

        $this->assertDatabaseHas('guest_demo_email_claims', [
            'email' => 'single.guest@example.com',
            'guest_demo_session_id' => $session->json('data.id'),
        ]);

        $this->postJson('/api/v1/guest-demos/email-verification', [
            'email' => 'SINGLE.GUEST@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_development_bypass_issues_a_grant_without_sending_email(): void
    {
        Mail::fake();
        config()->set('verification.email.development_bypass', true);
        $board = ExamBoard::create(['name' => 'Cambridge', 'code' => 'CAIE-BYPASS', 'status' => 'active', 'metadata' => []]);
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHY-BYPASS', 'status' => 'active', 'metadata' => []]);
        $course = Course::create(['exam_board_id' => $board->id, 'name' => 'O Level', 'code' => 'OL-BYPASS', 'level' => 'O Level', 'qualification_family' => 'O Level', 'status' => 'active', 'metadata' => []]);
        $course->subjects()->sync([$subject->id]);

        $grant = $this->postJson('/api/v1/guest-demos/email-verification', [
            'email' => 'Dev.Guest@Example.com',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'dev.guest@example.com')
            ->assertJsonPath('data.development_bypass', true)
            ->json('data.verification_grant');

        Mail::assertNothingSent();
        $this->assertIsString($grant);
        $this->assertSame(64, strlen($grant));

        $this->postJson('/api/v1/guest-demos', [
            'verification_grant' => $grant,
            'course_id' => $course->id,
            'exam_board_id' => $board->id,
            'subject_ids' => [$subject->id],
        ])->assertCreated()->assertJsonPath('data.email', 'dev.guest@example.com');

        $this->postJson('/api/v1/guest-demos/email-verification', [
            'email' => 'DEV.GUEST@example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_development_bypass_is_ignored_outside_safe_environments(): void
    {
        config()->set('verification.email.development_bypass', true);
        $original = app()->environment();

        try {
            app()->detectEnvironment(fn () => 'production');
            $this->assertFalse(app(DevelopmentEmailVerificationBypassService::class)->enabled());
        } finally {
            app()->detectEnvironment(fn () => $original);
            config()->set('verification.email.development_bypass', false);
        }
    }
}
