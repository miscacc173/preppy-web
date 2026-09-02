<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Plan;
use App\Models\Subject;
use App\Models\User;
use App\Services\PlanEligibilityService;
use App\Services\StudentOwnershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_plans_are_matched_to_the_students_course_and_subjects(): void
    {
        $physics = Subject::create(['name' => 'Physics', 'code' => 'PHYS', 'status' => 'active']);
        $chemistry = Subject::create(['name' => 'Chemistry', 'code' => 'CHEM', 'status' => 'active']);
        $oLevel = Course::create(['name' => 'O Level', 'code' => 'OL', 'level' => 'O Level', 'qualification_family' => 'O Level', 'status' => 'active']);
        $aLevel = Course::create(['name' => 'A Level', 'code' => 'AL', 'level' => 'A Level', 'qualification_family' => 'A Level', 'status' => 'active']);
        $user = User::factory()->create();
        $student = app(StudentOwnershipService::class)->createPlatformStudent($user, ['course_id' => $oLevel->id]);
        $student->subjects()->sync([$physics->id]);

        $coursePlan = $this->plan('O Level Access', 'OL-ACCESS', 'course', $oLevel->id);
        $wrongCoursePlan = $this->plan('A Level Access', 'AL-ACCESS', 'course', $aLevel->id);
        $physicsPlan = $this->plan('Physics', 'PHYS-ACCESS', 'subject');
        $physicsPlan->subjects()->sync([$physics->id]);
        $chemistryPlan = $this->plan('Chemistry', 'CHEM-ACCESS', 'subject');
        $chemistryPlan->subjects()->sync([$chemistry->id]);
        $allSubjects = $this->plan('Everything', 'ALL-ACCESS', 'all_subjects');

        $eligibility = app(PlanEligibilityService::class);
        $this->assertTrue($eligibility->isApplicable($student, $coursePlan));
        $this->assertFalse($eligibility->isApplicable($student, $wrongCoursePlan));
        $this->assertTrue($eligibility->isApplicable($student, $physicsPlan));
        $this->assertFalse($eligibility->isApplicable($student, $chemistryPlan));
        $this->assertTrue($eligibility->isApplicable($student, $allSubjects));
    }

    private function plan(string $name, string $code, string $scope, ?int $courseId = null): Plan
    {
        return Plan::create([
            'name' => $name, 'code' => $code, 'scope' => $scope, 'course_id' => $courseId,
            'billing_interval' => 'month', 'billing_interval_count' => 1,
            'price_minor' => 1000, 'currency' => 'PKR', 'trial_days' => 0,
            'is_public' => true, 'is_active' => true, 'features' => [], 'metadata' => [],
        ]);
    }
}
