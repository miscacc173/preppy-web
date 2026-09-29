<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class TestingInstituteStudentSeeder extends Seeder { public function run(): void { $this->call([PreppyInstituteStudentSeeder::class]); } }
