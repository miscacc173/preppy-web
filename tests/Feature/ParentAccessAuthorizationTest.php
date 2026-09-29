<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteUser;
use App\Models\User;
use App\Models\EducatorAccessGrant;
use App\Models\Subject;
use App\Services\RoleAssignmentService;
use App\Services\StudentOwnershipService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ParentAccessAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_relevant_institute_admin_can_list_link_and_revoke_parent_access(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        $institute = Institute::create(['name' => 'North School', 'slug' => 'north-school', 'code' => 'NORTH', 'status' => 'active', 'settings' => []]);
        $admin = User::factory()->create();
        $student = User::factory()->create();
        app(RoleAssignmentService::class)->assign($admin, 'institute_admin', 'institute', $institute->id);
        app(RoleAssignmentService::class)->assign($student, 'student');
        InstituteUser::create(['institute_id' => $institute->id, 'user_id' => $admin->id, 'status' => 'active', 'joined_at' => now()]);
        app(StudentOwnershipService::class)->createInstituteStudent($student, $institute, ['status' => 'active']);

        $linkId = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/students/{$student->id}/parents", [
            'parent_email' => 'parent@example.test',
            'parent_name' => 'Parent One',
            'relationship' => 'Parent',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/v1/students/{$student->id}/parents")
            ->assertOk()
            ->assertJsonPath('data.0.parent.email', 'parent@example.test');
        $this->deleteJson("/api/v1/parent-student-links/{$linkId}")->assertNoContent();
        $this->assertDatabaseMissing('parent_student_links', ['id' => $linkId]);
    }

    public function test_parent_teacher_and_unrelated_institute_admin_cannot_manage_links(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        $institute = Institute::create(['name' => 'North School', 'slug' => 'north-school', 'code' => 'NORTH', 'status' => 'active', 'settings' => []]);
        $otherInstitute = Institute::create(['name' => 'South School', 'slug' => 'south-school', 'code' => 'SOUTH', 'status' => 'active', 'settings' => []]);
        $student = User::factory()->create();
        app(RoleAssignmentService::class)->assign($student, 'student');
        app(StudentOwnershipService::class)->createInstituteStudent($student, $institute, ['status' => 'active']);

        foreach (['parent', 'teacher'] as $role) {
            $actor = User::factory()->create();
            app(RoleAssignmentService::class)->assign($actor, $role);
            $this->actingAs($actor, 'sanctum')->getJson("/api/v1/students/{$student->id}/parents")->assertForbidden();
            $this->postJson("/api/v1/students/{$student->id}/parents", ['parent_email' => "{$role}@example.test"])->assertForbidden();
        }

        $otherAdmin = User::factory()->create();
        app(RoleAssignmentService::class)->assign($otherAdmin, 'institute_admin', 'institute', $otherInstitute->id);
        InstituteUser::create(['institute_id' => $otherInstitute->id, 'user_id' => $otherAdmin->id, 'status' => 'active', 'joined_at' => now()]);
        $this->actingAs($otherAdmin, 'sanctum')->postJson("/api/v1/students/{$student->id}/parents", [
            'parent_email' => 'blocked@example.test',
        ])->assertForbidden();
    }

    public function test_only_system_admin_can_manage_platform_student_parent_access(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $student = User::factory()->create();
        app(RoleAssignmentService::class)->assign($admin, 'system_admin');
        app(RoleAssignmentService::class)->assign($student, 'student');
        app(StudentOwnershipService::class)->createPlatformStudent($student, ['status' => 'active']);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/students/{$student->id}/parents", [
            'parent_email' => 'platform-parent@example.test',
        ])->assertCreated();
        $this->getJson("/api/v1/students/{$student->id}/parents")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_teacher_can_manage_parents_only_when_admin_delegates_the_student_scope(): void
    {
        Mail::fake();
        $this->seed(RolePermissionSeeder::class);
        $institute = Institute::create(['name' => 'Delegation School', 'slug' => 'delegation-school', 'code' => 'DELEGATE', 'status' => 'active', 'settings' => []]);
        $admin = User::factory()->create(); $teacher = User::factory()->create(); $allowed = User::factory()->create(); $blocked = User::factory()->create();
        app(RoleAssignmentService::class)->assign($admin, 'institute_admin', 'institute', $institute->id);
        app(RoleAssignmentService::class)->assign($teacher, 'teacher');
        foreach ([$allowed, $blocked] as $student) {
            app(RoleAssignmentService::class)->assign($student, 'student');
            app(StudentOwnershipService::class)->createInstituteStudent($student, $institute, ['status' => 'active']);
        }
        foreach ([$admin, $teacher] as $member) InstituteUser::create(['institute_id' => $institute->id, 'user_id' => $member->id, 'status' => 'active', 'joined_at' => now()]);
        EducatorAccessGrant::create(['educator_user_id' => $teacher->id, 'institute_id' => $institute->id, 'scope_type' => 'student', 'scope_id' => $allowed->id, 'can_manage_parents' => true, 'status' => 'active', 'granted_by' => $admin->id]);

        $this->actingAs($teacher, 'sanctum')->postJson("/api/v1/students/{$allowed->id}/parents", ['parent_email' => 'delegated-parent@example.test'])->assertCreated();
        $this->getJson("/api/v1/students/{$allowed->id}/parents")->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/students/{$blocked->id}/parents", ['parent_email' => 'blocked-parent@example.test'])->assertForbidden();
    }

    public function test_teacher_subject_delegation_never_leaks_to_another_institute(): void
    {
        Mail::fake(); $this->seed(RolePermissionSeeder::class);
        $north = Institute::create(['name' => 'North', 'slug' => 'north-delegated', 'code' => 'NORTH-D', 'status' => 'active', 'settings' => []]);
        $south = Institute::create(['name' => 'South', 'slug' => 'south-delegated', 'code' => 'SOUTH-D', 'status' => 'active', 'settings' => []]);
        $teacher = User::factory()->create(); $northStudent = User::factory()->create(); $southStudent = User::factory()->create();
        app(RoleAssignmentService::class)->assign($teacher, 'teacher');
        foreach ([$northStudent, $southStudent] as $student) app(RoleAssignmentService::class)->assign($student, 'student');
        InstituteUser::create(['institute_id' => $north->id, 'user_id' => $teacher->id, 'status' => 'active', 'joined_at' => now()]);
        $northProfile = app(StudentOwnershipService::class)->createInstituteStudent($northStudent, $north, ['status' => 'active']);
        $southProfile = app(StudentOwnershipService::class)->createInstituteStudent($southStudent, $south, ['status' => 'active']);
        $subject = Subject::create(['name' => 'Physics', 'code' => 'PHYS-PARENT-SCOPE', 'status' => 'active']);
        $northProfile->subjects()->attach($subject->id, ['status' => 'active']); $southProfile->subjects()->attach($subject->id, ['status' => 'active']);
        EducatorAccessGrant::create(['educator_user_id' => $teacher->id, 'institute_id' => $north->id, 'scope_type' => 'subject', 'scope_id' => $subject->id, 'can_manage_parents' => true, 'status' => 'active', 'granted_by' => $teacher->id]);

        $this->actingAs($teacher, 'sanctum')->postJson("/api/v1/students/{$northStudent->id}/parents", ['parent_email' => 'north-parent@example.test'])->assertCreated();
        $this->postJson("/api/v1/students/{$southStudent->id}/parents", ['parent_email' => 'south-parent@example.test'])->assertForbidden();
    }
}
