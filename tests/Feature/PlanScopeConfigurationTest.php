<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanScopeConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_course_single_subject_and_multi_subject_plans(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        app(RoleAssignmentService::class)->assign($admin, 'system_admin');
        $physics = Subject::create(['name' => 'Physics', 'code' => 'PLAN-PHYSICS', 'status' => 'active']);
        $chemistry = Subject::create(['name' => 'Chemistry', 'code' => 'PLAN-CHEMISTRY', 'status' => 'active']);
        $course = Course::create(['name' => 'O Level Sciences', 'code' => 'PLAN-OLEVEL-SCI', 'level' => 'O Level', 'qualification_family' => 'CAIE', 'is_active' => true, 'metadata' => []]);

        $single = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/plans', $this->payload('Single Physics', 'SINGLE-PHYSICS', 'subject', ['subject_ids' => [$physics->id]]))
            ->assertCreated()
            ->assertJsonPath('data.subjects.0.id', $physics->id)
            ->json('data');

        $this->postJson('/api/v1/plans', $this->payload('Invalid Single', 'INVALID-SINGLE', 'subject', ['subject_ids' => [$physics->id, $chemistry->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('subject_ids');

        $this->postJson('/api/v1/plans', $this->payload('Science Pair', 'SCIENCE-PAIR', 'multi_subject', ['subject_ids' => [$physics->id, $chemistry->id]]))
            ->assertCreated()->assertJsonCount(2, 'data.subjects');

        $this->postJson('/api/v1/plans', $this->payload('Course Access', 'COURSE-ACCESS', 'course', ['course_id' => $course->id]))
            ->assertCreated()->assertJsonPath('data.course.id', $course->id)->assertJsonCount(0, 'data.subjects');

        $this->postJson('/api/v1/plans', $this->payload('Missing Course', 'MISSING-COURSE', 'course'))
            ->assertUnprocessable()->assertJsonValidationErrors('course_id');

        $this->putJson('/api/v1/plans/'.$single['id'], $this->payload('Single Physics', 'SINGLE-PHYSICS', 'course', ['course_id' => $course->id]))
            ->assertOk()->assertJsonPath('data.course.id', $course->id)->assertJsonCount(0, 'data.subjects');
    }

    private function payload(string $name, string $code, string $scope, array $target = []): array
    {
        return [...[
            'name' => $name,
            'code' => $code,
            'scope' => $scope,
            'course_id' => null,
            'subject_ids' => [],
            'billing_interval' => 'month',
            'billing_interval_count' => 1,
            'price_minor' => 2500,
            'currency' => 'GBP',
            'trial_days' => 0,
            'is_public' => true,
            'is_active' => true,
            'features' => [],
            'metadata' => [],
        ], ...$target];
    }
}
