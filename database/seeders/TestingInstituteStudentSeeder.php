<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestingInstituteStudentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@aistar.test')->value('id');
        $instituteAdminId = DB::table('users')->where('email', 'institute.admin@aistar.test')->value('id');
        $instituteStaffId = DB::table('users')->where('email', 'institute.staff@aistar.test')->value('id');

        DB::table('institutes')->updateOrInsert(
            ['code' => 'AISTAR-DEMO-001'],
            [
                'name' => 'AI Star Demo Academy',
                'slug' => 'ai-star-demo-academy',
                'status' => 'active',
                'billing_email' => 'billing@aistar.test',
                'phone' => '+92-300-0000000',
                'address' => 'Demo Campus, Main Boulevard',
                'city' => 'Lahore',
                'country_code' => 'PK',
                'timezone' => 'Asia/Karachi',
                'created_by' => $adminId,
                'settings' => json_encode(['student_limit' => 100, 'seeded' => true]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        $instituteId = DB::table('institutes')->where('code', 'AISTAR-DEMO-001')->value('id');

        foreach ([
            [$instituteAdminId, 'Principal'],
            [$instituteStaffId, 'Academic Coordinator'],
        ] as [$userId, $position]) {
            DB::table('institute_users')->updateOrInsert(
                ['institute_id' => $instituteId, 'user_id' => $userId],
                ['position' => $position, 'status' => 'active', 'invited_by' => $adminId, 'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $instituteRoleIds = [
            $instituteAdminId => DB::table('roles')->where('slug', 'institute_admin')->value('id'),
            $instituteStaffId => DB::table('roles')->where('slug', 'institute_staff')->value('id'),
        ];
        foreach ($instituteRoleIds as $userId => $roleId) {
            DB::table('user_roles')->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $roleId, 'context_type' => 'institute', 'context_id' => $instituteId],
                ['starts_at' => $now, 'assigned_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $courseId = DB::table('courses')->where('code', 'CAIE_O_LEVEL_2026')->value('id');
        $examBoardId = DB::table('exam_boards')->where('code', 'CAIE')->value('id');

        $students = [
            ['student.platform@aistar.test', 'platform', null, 'Year 10', 'Beacon School'],
            ['student.institute@aistar.test', 'institute', $instituteId, 'Year 10', 'AI Star Demo Academy'],
            ['student.institute2@aistar.test', 'institute', $instituteId, 'Year 11', 'AI Star Demo Academy'],
        ];

        foreach ($students as [$email, $ownership, $studentInstituteId, $schoolYear, $schoolName]) {
            $userId = DB::table('users')->where('email', $email)->value('id');
            DB::table('student_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'ownership_type' => $ownership,
                    'institute_id' => $studentInstituteId,
                    'course_id' => $courseId,
                    'exam_board_id' => $examBoardId,
                    'school_year' => $schoolYear,
                    'school_name' => $schoolName,
                    'date_of_birth' => '2010-04-12',
                    'status' => 'active',
                    'onboarded_at' => $now,
                    'preferences' => json_encode(['study_goal' => 'A grade', 'daily_minutes' => 45]),
                    'metadata' => json_encode(['seeded' => true]),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }

        foreach (['student.platform@aistar.test', 'student.institute@aistar.test', 'student.institute2@aistar.test'] as $email) {
            $profileId = DB::table('student_profiles')->where('user_id', DB::table('users')->where('email', $email)->value('id'))->value('id');
            foreach (['5054', '4024'] as $subjectCode) {
                $subjectId = DB::table('subjects')->where('code', $subjectCode)->where('syllabus_year_start', 2026)->value('id');
                DB::table('student_subjects')->updateOrInsert(
                    ['student_profile_id' => $profileId, 'subject_id' => $subjectId],
                    ['status' => 'active', 'started_at' => $now, 'ended_at' => null, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $parentId = DB::table('users')->where('email', 'parent@aistar.test')->value('id');
        $platformStudentId = DB::table('users')->where('email', 'student.platform@aistar.test')->value('id');
        DB::table('parent_student_links')->updateOrInsert(
            ['parent_user_id' => $parentId, 'student_user_id' => $platformStudentId],
            ['relationship' => 'Father', 'status' => 'active', 'can_view_performance' => true, 'can_view_subscription' => true, 'invited_by' => $platformStudentId, 'accepted_at' => $now, 'created_at' => $now, 'updated_at' => $now]
        );

        $token = 'demo-institute-invite-token';
        DB::table('institute_invites')->updateOrInsert(
            ['token_hash' => hash('sha256', $token)],
            ['institute_id' => $instituteId, 'email' => 'new.staff@aistar.test', 'role_slug' => 'institute_staff', 'expires_at' => $now->copy()->addDays(7), 'accepted_at' => null, 'invited_by' => $instituteAdminId, 'metadata' => json_encode(['plain_token_for_testing' => $token]), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('guest_demo_sessions')->updateOrInsert(
            ['id' => '11111111-1111-4111-8111-111111111111'],
            ['email' => 'guest@aistar.test', 'device_fingerprint' => 'postman-demo-device', 'status' => 'active', 'started_at' => $now, 'expires_at' => $now->copy()->addDays(7), 'converted_at' => null, 'converted_user_id' => null, 'progress_snapshot' => json_encode(['subject_code' => '5054', 'questions_attempted' => 12, 'correct_answers' => 9]), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );
    }
}
