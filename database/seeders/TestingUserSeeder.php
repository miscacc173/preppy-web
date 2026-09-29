<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class TestingUserSeeder extends Seeder { public function run(): void { $this->call([PreppyUserSeeder::class]); } }
