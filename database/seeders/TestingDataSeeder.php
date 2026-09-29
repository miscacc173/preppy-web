<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class TestingDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TestingUserSeeder::class,
            TestingAcademicDataSeeder::class,
            TopicSeeder::class,
            TestingInstituteStudentSeeder::class,
            TestingPlanBillingSeeder::class,
            PreppyAppleIapSeeder::class,
            TestingNotificationAuditSeeder::class,
        ]);
    }
}
