<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\LearningSession;
use App\Models\ParentStudentLink;
use App\Models\Plan;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentOwnershipService;
use App\Services\SubscriptionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleLoginAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_invite_creates_login_and_only_returns_linked_student_content(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $student = User::factory()->create();
        $this->assignRole($admin, 'system_admin');
        $this->assignRole($student, 'student');
        app(StudentOwnershipService::class)->createPlatformStudent($student, ['school_year' => 'Year 11']);
        $physics = Subject::create(['name' => 'Physics', 'code' => 'PHYS', 'status' => 'active']);
        $mathematics = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'status' => 'active']);
        $student->studentProfile->subjects()->sync([$physics->id, $mathematics->id]);
        $plan = Plan::create([
            'name' => 'Physics Monthly', 'code' => 'PHYSICS-PARENT-VISIBILITY', 'scope' => 'subject',
            'billing_interval' => 'month', 'billing_interval_count' => 1, 'price_minor' => 1200,
            'currency' => 'PKR', 'trial_days' => 0, 'is_public' => true, 'is_active' => true,
            'features' => [], 'metadata' => [],
        ]);
        $plan->subjects()->sync([$physics->id]);
        $subscription = app(SubscriptionService::class)->subscribeStudent($student->studentProfile, $plan, $admin->id);
        $subscription->update(['status' => 'active']);

        $invite = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/students/{$student->id}/parents", [
            'parent_email' => 'parent@example.test',
            'parent_name' => 'Test Parent',
            'relationship' => 'Mother',
            'can_view_subscription' => false,
        ])->assertCreated();
        $token = $invite->json('invite_token');

        $this->getJson("/api/v1/parent-invites/{$token}")->assertOk()->assertJsonPath('data.student_name', $student->name);
        $activated = $this->postJson("/api/v1/parent-invites/{$token}/activate", [
            'name' => 'Test Parent', 'password' => 'Password@123', 'password_confirmation' => 'Password@123',
        ])->assertOk()->assertJsonPath('data.roles.0.slug', 'parent');

        $parent = User::where('email', 'parent@example.test')->firstOrFail();
        LearningSession::create([
            'user_id' => $student->id,
            'session_uid' => 'parent-progress-session',
            'session_type' => 'exam',
            'subject' => 'Physics',
            'topic' => 'Forces and motion',
            'status' => 'completed',
            'earned' => 8,
            'total_marks' => 10,
            'accuracy' => 80,
            'grade' => 'A',
            'time_seconds' => 900,
            'average_seconds_per_question' => 180,
            'questions' => [
                ['q' => 'Question one', 'correct' => true, 'skipped' => false],
                ['q' => 'Question two', 'correct' => false, 'skipped' => false],
            ],
            'metadata' => [],
            'started_at' => now()->subMinutes(15),
            'completed_at' => now(),
        ]);
        LearningSession::create([
            'user_id' => $student->id,
            'session_uid' => 'out-of-plan-parent-progress-session',
            'session_type' => 'learn',
            'subject' => 'Mathematics',
            'topic' => 'Algebra',
            'status' => 'completed',
            'earned' => 10,
            'total_marks' => 10,
            'accuracy' => 100,
            'grade' => 'A*',
            'time_seconds' => 600,
            'average_seconds_per_question' => 120,
            'questions' => [['q' => 'Out-of-plan question', 'correct' => true, 'skipped' => false]],
            'metadata' => [],
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
        $this->actingAs($parent, 'sanctum')->getJson('/api/v1/parent-students')
            ->assertOk()->assertJsonPath('data.0.student.name', $student->name)
            ->assertJsonPath('data.0.student.school_year', 'Year 11')
            ->assertJsonPath('data.0.student.subjects', ['Physics'])
            ->assertJsonCount(1, 'data.0.performance.subject_readiness')
            ->assertJsonPath('data.0.performance.subject_readiness.0.subject', 'Physics')
            ->assertJsonPath('data.0.performance.subject_readiness.0.latest_score', 80)
            ->assertJsonPath('data.0.performance.subject_readiness.0.recent_sessions.0.earned', 8)
            ->assertJsonPath('data.0.performance.subject_readiness.0.recent_sessions.0.question_count', 2)
            ->assertJsonPath('data.0.subscription', null)
            ->assertJsonMissing(['subject' => 'Mathematics']);
        $this->assertNotEmpty($activated->json('token'));
    }

    public function test_institute_invite_sets_password_and_scoped_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $this->assignRole($admin, 'system_admin');
        $institute = Institute::create(['name' => 'Test Institute', 'slug' => 'test-institute', 'code' => 'TEST', 'status' => 'active', 'settings' => []]);

        $invite = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/institutes/{$institute->id}/members/invite", [
            'email' => 'staff@example.test', 'role_slug' => 'institute_staff', 'expires_in_days' => 7,
        ])->assertCreated();
        $token = $invite->json('invite_token');
        $this->getJson("/api/v1/institute-invites/{$token}")->assertOk()->assertJsonPath('data.institute', 'Test Institute');
        $this->postJson('/api/v1/institute-invites/accept', [
            'token' => $token, 'name' => 'Institute Staff', 'password' => 'Password@123', 'password_confirmation' => 'Password@123',
        ])->assertOk()->assertJsonPath('data.roles.0.slug', 'institute_staff');

        $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'Password@123'])
            ->assertOk()->assertJsonPath('data.roles.0.pivot.context_id', $institute->id);
    }

    public function test_parent_portal_returns_every_linked_student_with_its_name(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $parent = User::factory()->create();
        $firstStudent = User::factory()->create(['name' => 'First Student']);
        $secondStudent = User::factory()->create(['name' => 'Second Student']);
        $this->assignRole($parent, 'parent');

        foreach ([$firstStudent, $secondStudent] as $student) {
            $this->assignRole($student, 'student');
            app(StudentOwnershipService::class)->createPlatformStudent($student, ['status' => 'active']);
            ParentStudentLink::create([
                'parent_user_id' => $parent->id,
                'student_user_id' => $student->id,
                'relationship' => 'Parent',
                'status' => 'active',
                'can_view_performance' => true,
                'can_view_subscription' => false,
                'accepted_at' => now(),
            ]);
        }

        $this->actingAs($parent, 'sanctum')->getJson('/api/v1/parent-students')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $firstStudent->id, 'name' => 'First Student'])
            ->assertJsonFragment(['id' => $secondStudent->id, 'name' => 'Second Student']);
    }

    public function test_existing_account_invite_requires_current_password_and_does_not_reset_it(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $student = User::factory()->create();
        $parent = User::factory()->create(['email' => 'existing-parent@example.test', 'password' => 'OriginalPassword@123']);
        $this->assignRole($admin, 'system_admin');
        $this->assignRole($student, 'student');
        app(StudentOwnershipService::class)->createPlatformStudent($student, ['school_year' => 'Year 10']);

        $token = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/students/{$student->id}/parents", [
            'parent_email' => $parent->email,
        ])->assertCreated()->json('invite_token');

        $this->getJson("/api/v1/parent-invites/{$token}")
            ->assertOk()->assertJsonPath('data.existing_account', true);
        $this->postJson("/api/v1/parent-invites/{$token}/activate", ['current_password' => 'wrong-password'])
            ->assertStatus(422);
        $this->postJson("/api/v1/parent-invites/{$token}/activate", ['current_password' => 'OriginalPassword@123'])
            ->assertOk();

        $this->assertTrue(Hash::check('OriginalPassword@123', $parent->fresh()->password));
    }

    private function assignRole(User $user, string $slug): void
    {
        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => DB::table('roles')->where('slug', $slug)->value('id'),
            'context_type' => 'system', 'context_id' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
