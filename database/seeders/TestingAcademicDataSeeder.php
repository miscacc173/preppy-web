<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingAcademicDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $cambridgeId = DB::table('exam_boards')->where('code', 'CAIE')->value('id');

        $subjects = [
            ['Physics', '5054'],
            ['Chemistry', '5070'],
            ['Biology', '5090'],
            ['Mathematics', '4024'],
            ['Computer Science', '2210'],
            ['English Language', '1123'],
        ];

        foreach ($subjects as [$name, $code]) {
            DB::table('subjects')->updateOrInsert(
                ['code' => $code, 'syllabus_year_start' => 2026, 'syllabus_year_end' => 2028],
                [
                    'name' => $name,
                    'status' => 'active',
                    'metadata' => json_encode(['exam_board_code' => 'CAIE', 'seeded' => true]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $courses = [
            ['Cambridge O Level', 'CAIE_O_LEVEL_2026', 'o_level'],
            ['Cambridge IGCSE', 'CAIE_IGCSE_2026', 'igcse'],
            ['Cambridge AS Level', 'CAIE_AS_LEVEL_2026', 'as_level'],
            ['Cambridge A Level', 'CAIE_A_LEVEL_2026', 'a_level'],
        ];

        foreach ($courses as [$name, $code, $level]) {
            DB::table('courses')->updateOrInsert(
                ['exam_board_id' => $cambridgeId, 'code' => $code, 'start_year' => 2026],
                [
                    'name' => $name,
                    'level' => $level,
                    'qualification_family' => $level,
                    'end_year' => 2028,
                    'is_active' => true,
                    'metadata' => json_encode(['seeded' => true]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $oLevelCourseId = DB::table('courses')->where('code', 'CAIE_O_LEVEL_2026')->value('id');
        foreach (['5054', '5070', '5090', '4024', '2210', '1123'] as $index => $subjectCode) {
            $subjectId = DB::table('subjects')->where('code', $subjectCode)->where('syllabus_year_start', 2026)->value('id');
            DB::table('course_subjects')->updateOrInsert(
                ['course_id' => $oLevelCourseId, 'subject_id' => $subjectId],
                ['is_compulsory' => in_array($subjectCode, ['4024', '1123'], true), 'display_order' => $index + 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
