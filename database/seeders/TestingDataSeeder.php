<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                TestingUserSeeder::class,
                TestingAcademicDataSeeder::class,
                TopicSeeder::class,
                TestingInstituteStudentSeeder::class,
                TestingPlanBillingSeeder::class,
                TestingNotificationAuditSeeder::class,
            ]);
        });
    }
}
