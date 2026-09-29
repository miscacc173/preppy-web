<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteUser;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_institute_admin_only_lists_and_updates_own_institute(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owned = Institute::create(['name' => 'Owned', 'slug' => 'owned', 'code' => 'OWN']);
        $other = Institute::create(['name' => 'Other', 'slug' => 'other', 'code' => 'OTH']);
        $admin = User::factory()->create();
        $roleId = DB::table('roles')->where('slug', 'institute_admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $admin->id,
            'role_id' => $roleId,
            'context_type' => 'institute',
            'context_id' => $owned->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        InstituteUser::create([
            'institute_id' => $owned->id,
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/institutes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $owned->id);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/institutes/{$owned->id}", [
                'name' => 'Owned Updated',
                'slug' => $owned->slug,
                'code' => $owned->code,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Owned Updated');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/institutes/{$other->id}")
            ->assertForbidden();
    }

    public function test_role_assignment_rejects_a_context_that_does_not_match_role_scope(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $adminRole = DB::table('roles')->where('slug', 'system_admin')->value('id');
        DB::table('user_roles')->insert([
            'user_id' => $admin->id,
            'role_id' => $adminRole,
            'context_type' => 'system',
            'context_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/users/{$target->id}/roles", [
                'roles' => [[
                    'slug' => 'institute_admin',
                    'context_type' => 'system',
                    'context_id' => null,
                ]],
            ])
            ->assertUnprocessable();
    }

    public function test_institute_admin_can_update_members_only_in_own_context(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owned = Institute::create(['name' => 'Owned', 'slug' => 'owned-members', 'code' => 'OWN-M']);
        $other = Institute::create(['name' => 'Other', 'slug' => 'other-members', 'code' => 'OTH-M']);
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $roleId = DB::table('roles')->where('slug', 'institute_admin')->value('id');
        DB::table('user_roles')->insert(['user_id' => $admin->id, 'role_id' => $roleId, 'context_type' => 'institute', 'context_id' => $owned->id, 'created_at' => now(), 'updated_at' => now()]);

        // ADD THIS HERE
        InstituteUser::create([
            'institute_id' => $owned->id,
            'user_id' => $admin->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        InstituteUser::create([
            'institute_id' => $owned->id,
            'user_id' => $member->id,
            'position' => 'Teacher',
            'status' => 'active',
        ]);

        InstituteUser::create([
            'institute_id' => $other->id,
            'user_id' => $member->id,
            'position' => 'Teacher',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/institutes/{$owned->id}/members/{$member->id}", [
                'position' => 'Head Teacher',
                'status' => 'active',
                'role_slug' => 'institute_staff',
            ])
            ->assertOk()
            ->assertJsonPath('data.position', 'Head Teacher');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/institutes/{$other->id}/members/{$member->id}", [
                'position' => 'Forbidden',
                'status' => 'active',
            ])
            ->assertForbidden();
    }
}
