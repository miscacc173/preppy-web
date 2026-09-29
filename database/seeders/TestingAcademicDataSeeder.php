<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class TestingAcademicDataSeeder extends Seeder { public function run(): void { $this->call([PreppyAcademicSeeder::class]); } }
