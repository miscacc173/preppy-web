<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class TestingNotificationAuditSeeder extends Seeder { public function run(): void { $this->call([PreppyNotificationAuditSeeder::class]); } }
