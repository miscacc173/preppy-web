<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreppyDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                RolePermissionSeeder::class,
                PreppyAcademicSeeder::class,
                TopicSeeder::class,
                PreppyUserSeeder::class,
                PreppyInstituteStudentSeeder::class,
                PreppyPlanSeeder::class,
                PreppyBillingLifecycleSeeder::class,
                PreppyPromotionReferralSeeder::class,
                PreppyAppleIapSeeder::class,
                PreppyNotificationAuditSeeder::class,
            ]);
        });
    }
}
