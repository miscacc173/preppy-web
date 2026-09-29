<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class AppleIapProductSeeder extends Seeder { public function run(): void { $this->call([PreppyAppleIapSeeder::class]); } }
