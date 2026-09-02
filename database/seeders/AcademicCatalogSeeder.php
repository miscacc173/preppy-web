<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $boards = [['Cambridge International', 'CAIE'], ['Pearson Edexcel', 'EDEXCEL'], ['AQA', 'AQA'], ['OCR', 'OCR']];
        foreach ($boards as [$name, $code]) DB::table('exam_boards')->updateOrInsert(['code' => $code], ['name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $courses = [['O Level', 'O_LEVEL', 'o_level'], ['IGCSE', 'IGCSE', 'igcse'], ['GCSE', 'GCSE', 'gcse'], ['AS Level', 'AS_LEVEL', 'as_level'], ['A Level', 'A_LEVEL', 'a_level']];
        foreach ($courses as [$name, $code, $level]) DB::table('courses')->updateOrInsert(['code' => $code, 'exam_board_id' => null, 'start_year' => null], ['name' => $name, 'level' => $level, 'qualification_family' => $level, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
