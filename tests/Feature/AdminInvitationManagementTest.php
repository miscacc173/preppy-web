<?php

namespace Tests\Feature;

use App\Mail\AccessInvitationMail;
use App\Models\EducatorInvite;
use App\Models\Institute;
use App\Models\InstituteInvite;
use App\Models\InstituteUser;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminInvitationManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        app(RoleAssignmentService::class)->assign($admin, 'system_admin');
        return $admin;
    }

    public function test_admin_lists_and_manually_activates_educator_invitation_transactionally(): void
    {
        $admin = $this->admin();
        $institute = Institute::create(['name' => 'North School', 'slug' => 'north-school', 'code' => 'NORTH', 'status' => 'active', 'settings' => []]);
        $token = Str::random(64);
        $invite = EducatorInvite::create([
            'email' => 'teacher@example.test', 'role_slug' => 'teacher', 'institute_id' => $institute->id,
            'token_hash' => hash('sha256', $token), 'expires_at' => now()->subDay(), 'invited_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/invitations')
            ->assertOk()->assertJsonPath('data.0.email', 'teacher@example.test');

        $this->postJson("/api/v1/admin/invitations/educator/{$invite->id}/activate")->assertOk();

        $user = User::where('email', 'teacher@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('teacher'));
        $this->assertDatabaseHas('institute_users', ['institute_id' => $institute->id, 'user_id' => $user->id, 'status' => 'active']);
        $this->assertNotNull($invite->fresh()->accepted_at);
    }

    public function test_admin_manually_activates_institute_invitation_with_scoped_role(): void
    {
        $admin = $this->admin();
        $institute = Institute::create(['name' => 'South School', 'slug' => 'south-school', 'code' => 'SOUTH', 'status' => 'active', 'settings' => []]);
        $invite = InstituteInvite::create([
            'institute_id' => $institute->id, 'email' => 'staff@example.test', 'role_slug' => 'institute_staff',
            'token_hash' => hash('sha256', Str::random(64)), 'expires_at' => now()->addDays(2), 'invited_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/invitations/institute/{$invite->id}/activate")->assertOk();

        $user = User::where('email', 'staff@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('institute_staff', 'institute', $institute->id));
        $this->assertDatabaseHas('institute_users', ['institute_id' => $institute->id, 'user_id' => $user->id, 'status' => 'active']);
    }

    public function test_admin_manually_activates_parent_student_link(): void
    {
        $admin = $this->admin();
        $parent = User::factory()->create(['status' => 'pending']);
        $student = User::factory()->create();
        $link = ParentStudentLink::create([
            'parent_user_id' => $parent->id, 'student_user_id' => $student->id, 'relationship' => 'Parent',
            'status' => 'pending', 'can_view_performance' => true, 'can_view_subscription' => false,
            'invited_by' => $admin->id, 'invite_token_hash' => hash('sha256', Str::random(64)), 'invite_expires_at' => now()->addDays(2),
        ]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/invitations/parent/{$link->id}/activate")->assertOk();

        $this->assertSame('active', $parent->fresh()->status->value);
        $this->assertTrue($parent->fresh()->hasRole('parent'));
        $this->assertDatabaseHas('parent_student_links', ['id' => $link->id, 'status' => 'active', 'parent_user_id' => $parent->id, 'student_user_id' => $student->id]);
        $this->assertNull($link->fresh()->invite_token_hash);
    }

    public function test_copy_resend_extend_and_revoke_manage_existing_pending_invite(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $invite = EducatorInvite::create([
            'email' => 'tutor@example.test', 'role_slug' => 'tutor', 'institute_id' => null,
            'token_hash' => hash('sha256', Str::random(64)), 'expires_at' => now()->addDay(), 'invited_by' => $admin->id,
        ]);

        $link = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/invitations/educator/{$invite->id}/link")
            ->assertOk()->json('data');
        $this->assertStringStartsWith('/educator/activate?token=', $link['activation_path']);
        $this->assertSame(hash('sha256', $link['invite_token']), $invite->fresh()->token_hash);

        $oldExpiry = $invite->fresh()->expires_at;
        $this->postJson("/api/v1/admin/invitations/educator/{$invite->id}/extend", ['days' => 7])->assertOk();
        $this->assertTrue($invite->fresh()->expires_at->greaterThan($oldExpiry));

        $this->postJson("/api/v1/admin/invitations/educator/{$invite->id}/resend")->assertOk();
        Mail::assertQueued(AccessInvitationMail::class);

        $this->deleteJson("/api/v1/admin/invitations/educator/{$invite->id}")->assertNoContent();
        $this->assertDatabaseMissing('educator_invites', ['id' => $invite->id]);
    }
}
