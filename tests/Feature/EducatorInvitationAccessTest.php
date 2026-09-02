<?php

namespace Tests\Feature;

use App\Mail\AccessInvitationMail;
use App\Models\Institute;
use App\Models\InstituteUser;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EducatorInvitationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_institute_admin_can_invite_teacher_but_not_tutor(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        $institute = Institute::create(['name' => 'North School', 'slug' => 'north-school', 'code' => 'NORTH', 'status' => 'active', 'settings' => []]);
        $admin = User::factory()->create();
        app(RoleAssignmentService::class)->assign($admin, 'institute_admin', 'institute', $institute->id);
        InstituteUser::create(['institute_id' => $institute->id, 'user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/educator-invites', [
            'email' => 'teacher@example.test', 'role_slug' => 'teacher', 'institute_id' => $institute->id,
        ])->assertCreated();
        Mail::assertQueued(AccessInvitationMail::class);

        $this->postJson('/api/v1/educator-invites', [
            'email' => 'tutor@example.test', 'role_slug' => 'tutor', 'institute_id' => $institute->id,
        ])->assertForbidden();

        $token = $response->json('invite_token');
        $this->postJson("/api/v1/educator-invites/{$token}/accept", [
            'name' => 'Teacher One', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertOk()->assertJsonPath('data.roles.0.slug', 'teacher');
    }

    public function test_teacher_visibility_requires_an_explicit_grant(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $institute = Institute::create(['name' => 'Grant School', 'slug' => 'grant-school', 'code' => 'GRANT', 'status' => 'active', 'settings' => []]);
        $admin = User::factory()->create(); $teacher = User::factory()->create(); $student = User::factory()->create();
        app(RoleAssignmentService::class)->assign($admin, 'institute_admin', 'institute', $institute->id);
        app(RoleAssignmentService::class)->assign($teacher, 'teacher');
        app(RoleAssignmentService::class)->assign($student, 'student');
        foreach ([$admin, $teacher] as $member) InstituteUser::create(['institute_id' => $institute->id, 'user_id' => $member->id, 'status' => 'active', 'joined_at' => now()]);
        app(StudentOwnershipService::class)->createInstituteStudent($student, $institute, ['status' => 'active']);

        $this->actingAs($teacher, 'sanctum')->getJson('/api/v1/teacher/students')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/educators/{$teacher->id}/access-grants", [
            'institute_id' => $institute->id, 'scope_type' => 'student', 'scope_id' => $student->id,
        ])->assertCreated();
        $this->actingAs($teacher, 'sanctum')->getJson('/api/v1/teacher/students')->assertOk()->assertJsonPath('data.0.user_id', $student->id);
    }
}
