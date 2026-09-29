<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class TestingPlanBillingSeeder extends Seeder { public function run(): void { $this->call([PreppyPlanSeeder::class, PreppyBillingLifecycleSeeder::class, PreppyPromotionReferralSeeder::class]); } }
