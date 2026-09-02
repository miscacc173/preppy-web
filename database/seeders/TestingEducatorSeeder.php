<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingEducatorSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@preppy.test')->value('id');
        $teacherId = DB::table('users')->where('email', 'teacher@preppy.test')->value('id');
        $tutorId = DB::table('users')->where('email', 'tutor@preppy.test')->value('id');
        $instituteId = DB::table('institutes')->where('code', 'PREPPY-DEMO-001')->value('id');
        $courseId = DB::table('courses')->where('code', 'CAIE_O_LEVEL_2026')->value('id');
        $physicsId = DB::table('subjects')->where('code', '5054')->where('syllabus_year_start', 2026)->value('id');

        DB::table('institute_users')->updateOrInsert(
            ['institute_id' => $instituteId, 'user_id' => $teacherId],
            ['position' => 'Physics Teacher', 'status' => 'active', 'invited_by' => $adminId, 'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]
        );

        $teacherRoleId = DB::table('roles')->where('slug', 'teacher')->value('id');
        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $teacherId, 'role_id' => $teacherRoleId, 'context_type' => 'institute', 'context_id' => $instituteId],
            ['starts_at' => $now, 'ends_at' => null, 'assigned_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('teaching_classes')->updateOrInsert(
            ['institute_id' => $instituteId, 'name' => 'O Level Physics - Demo'],
            ['course_id' => $courseId, 'subject_id' => $physicsId, 'teacher_user_id' => $teacherId, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]
        );
        $classId = DB::table('teaching_classes')->where('institute_id', $instituteId)->where('name', 'O Level Physics - Demo')->value('id');

        foreach (['student.institute@preppy.test', 'student.institute2@preppy.test'] as $email) {
            $studentId = DB::table('users')->where('email', $email)->value('id');
            DB::table('teaching_class_students')->updateOrInsert(
                ['teaching_class_id' => $classId, 'student_user_id' => $studentId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        foreach ([
            [$teacherId, 'class', $classId, true],
            [$teacherId, 'subject', $physicsId, false],
            [$tutorId, 'subject', $physicsId, false],
        ] as [$educatorId, $scopeType, $scopeId, $canManageParents]) {
            DB::table('educator_access_grants')->updateOrInsert(
                ['educator_user_id' => $educatorId, 'scope_type' => $scopeType, 'scope_id' => $scopeId],
                ['institute_id' => $scopeType === 'class' ? $instituteId : null, 'can_manage_parents' => $canManageParents, 'status' => 'active', 'granted_by' => $adminId, 'revoked_at' => null, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $token = 'demo-educator-invite-token';
        DB::table('educator_invites')->updateOrInsert(
            ['token_hash' => hash('sha256', $token)],
            ['email' => 'pending.teacher@preppy.test', 'role_slug' => 'teacher', 'institute_id' => $instituteId, 'expires_at' => $now->copy()->addDays(7), 'accepted_at' => null, 'invited_by' => $adminId, 'metadata' => json_encode(['plain_token_for_testing' => $token]), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('tutor_question_feedback')->updateOrInsert(
            ['tutor_user_id' => $tutorId, 'session_type' => 'learn', 'session_id' => 'SEED-LEARN-001', 'question_key' => 'Q1'],
            ['question_text' => 'A car accelerates uniformly. Explain what this means.', 'answer_text' => 'Its speed increases by the same amount each second.', 'feedback' => 'Correct. This describes constant acceleration.', 'verdict' => 'correct', 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );
    }
}
