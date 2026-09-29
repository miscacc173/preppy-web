<?php

namespace Tests\Feature;

use App\Enums\StudentOwnershipType;
use App\Models\Institute;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentOwnershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_student_can_be_attached_to_institute(): void
    {
        $user = User::factory()->create();
        $student = StudentProfile::create(['user_id' => $user->id, 'ownership_type' => 'platform']);
        $institute = Institute::create(['name' => 'Demo', 'slug' => 'demo', 'code' => 'DEMO']);
        $result = app(StudentOwnershipService::class)->attachExistingStudent($student, $institute, $user->id);
        $this->assertSame(StudentOwnershipType::Institute, $result->ownership_type);
        $this->assertSame($institute->id, $result->institute_id);
    }

    public function test_profile_creation_discards_attributes_from_the_users_payload(): void
    {
        $user = User::factory()->create();

        $profile = app(StudentOwnershipService::class)->createPlatformStudent($user, [
            'name' => 'Must Not Reach Profile',
            'email' => 'wrong-table@example.test',
            'password' => 'MustNotReachProfile',
            'subject_ids' => [999],
            'school_year' => 'Year 11',
        ]);

        $this->assertSame('Year 11', $profile->school_year);
        $this->assertArrayNotHasKey('name', $profile->getAttributes());
        $this->assertArrayNotHasKey('email', $profile->getAttributes());
        $this->assertArrayNotHasKey('password', $profile->getAttributes());
        $this->assertArrayNotHasKey('subject_ids', $profile->getAttributes());
    }
}
