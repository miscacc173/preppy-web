<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreppyAcademicSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $boards = [
            ['Cambridge International', 'CAIE'],
            ['Pearson Edexcel', 'EDEXCEL'],
            ['AQA', 'AQA'],
            ['OCR', 'OCR'],
        ];
        foreach ($boards as [$name, $code]) {
            DB::table('exam_boards')->updateOrInsert(['code' => $code], [
                'name' => $name, 'is_active' => true, 'status' => 'active',
                'metadata' => json_encode(['seeded' => true, 'platform' => 'Preppy']),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $cambridgeId = DB::table('exam_boards')->where('code', 'CAIE')->value('id');
        $courseRows = [
            ['Cambridge O Level', 'CAIE_O_LEVEL_2026', 'o_level'],
            ['Cambridge IGCSE', 'CAIE_IGCSE_2026', 'igcse'],
            ['Cambridge AS Level', 'CAIE_AS_LEVEL_2026', 'as_level'],
            ['Cambridge A Level', 'CAIE_A_LEVEL_2026', 'a_level'],
        ];
        foreach ($courseRows as [$name, $code, $level]) {
            DB::table('courses')->updateOrInsert(
                ['exam_board_id' => $cambridgeId, 'code' => $code, 'start_year' => 2026],
                [
                    'name' => $name, 'level' => $level, 'qualification_family' => $level,
                    'end_year' => 2028, 'is_active' => true, 'status' => 'active',
                    'metadata' => json_encode(['seeded' => true, 'platform' => 'Preppy']),
                    'created_at' => $now, 'updated_at' => $now,
                ]
            );
        }

        // Generic qualification records remain useful in admin course management.
        $generic = [
            ['O Level', 'O_LEVEL', 'o_level'], ['IGCSE', 'IGCSE', 'igcse'], ['GCSE', 'GCSE', 'gcse'],
            ['AS Level', 'AS_LEVEL', 'as_level'], ['A Level', 'A_LEVEL', 'a_level'],
        ];
        foreach ($generic as [$name, $code, $level]) {
            DB::table('courses')->updateOrInsert(
                ['exam_board_id' => null, 'code' => $code, 'start_year' => null],
                ['name' => $name, 'level' => $level, 'qualification_family' => $level, 'is_active' => true, 'status' => 'active', 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $subjects = [
            ['Physics', '5054'], ['Chemistry', '5070'], ['Biology', '5090'],
            ['Mathematics', '4024'], ['Computer Science', '2210'], ['English Language', '1123'],
        ];
        foreach ($subjects as [$name, $code]) {
            DB::table('subjects')->updateOrInsert(
                ['code' => $code, 'syllabus_year_start' => 2026, 'syllabus_year_end' => 2028],
                ['name' => $name, 'status' => 'active', 'metadata' => json_encode(['exam_board_code' => 'CAIE', 'seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $assignments = [
            'CAIE_O_LEVEL_2026' => ['5054', '5070', '5090', '4024', '2210', '1123'],
            'CAIE_IGCSE_2026' => ['5054', '5070', '5090', '4024', '2210', '1123'],
            'CAIE_AS_LEVEL_2026' => ['5054', '5070', '5090', '4024'],
            'CAIE_A_LEVEL_2026' => ['5054', '5070', '5090', '4024'],
        ];
        foreach ($assignments as $courseCode => $subjectCodes) {
            $courseId = DB::table('courses')->where('code', $courseCode)->value('id');
            DB::table('course_exam_boards')->updateOrInsert(
                ['course_id' => $courseId, 'exam_board_id' => $cambridgeId],
                ['created_at' => $now, 'updated_at' => $now]
            );
            foreach ($subjectCodes as $index => $subjectCode) {
                $subjectId = DB::table('subjects')->where('code', $subjectCode)->where('syllabus_year_start', 2026)->value('id');
                DB::table('course_subjects')->updateOrInsert(
                    ['course_id' => $courseId, 'subject_id' => $subjectId],
                    ['is_compulsory' => in_array($subjectCode, ['4024', '1123'], true), 'display_order' => $index + 1, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
