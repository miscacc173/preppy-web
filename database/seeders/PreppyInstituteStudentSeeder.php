<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class PreppyInstituteStudentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = $this->uid('admin@preppy.test');
        $instituteAdminId = $this->uid('institute.admin@preppy.test');
        $instituteStaffId = $this->uid('institute.staff@preppy.test');

        $institutes = [
            ['PREPPY-DEMO-001', 'Preppy Demo Academy', 'preppy-demo-academy', 'billing@preppy.test', 'Lahore'],
            ['PREPPY-DEMO-002', 'Preppy Partner School', 'preppy-partner-school', 'partner.billing@preppy.test', 'Karachi'],
        ];
        foreach ($institutes as [$code, $name, $slug, $billingEmail, $city]) {
            DB::table('institutes')->updateOrInsert(['code' => $code], [
                'name' => $name, 'slug' => $slug, 'status' => 'active', 'billing_email' => $billingEmail,
                'phone' => '+92-300-0000000', 'address' => 'Preppy Demo Campus', 'city' => $city,
                'country_code' => 'PK', 'timezone' => 'Asia/Karachi', 'created_by' => $adminId,
                'settings' => json_encode(['student_limit' => 100, 'seeded' => true, 'platform' => 'Preppy']),
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
            ]);
        }
        $instituteId = DB::table('institutes')->where('code', 'PREPPY-DEMO-001')->value('id');

        foreach ([[$instituteAdminId, 'Principal', 'institute_admin'], [$instituteStaffId, 'Academic Coordinator', 'institute_staff']] as [$userId, $position, $roleSlug]) {
            DB::table('institute_users')->updateOrInsert(
                ['institute_id' => $instituteId, 'user_id' => $userId],
                ['position' => $position, 'status' => 'active', 'invited_by' => $adminId, 'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]
            );
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            DB::table('user_roles')->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $roleId, 'context_type' => 'institute', 'context_id' => $instituteId],
                ['starts_at' => $now, 'ends_at' => null, 'assigned_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $oLevelId = DB::table('courses')->where('code', 'CAIE_O_LEVEL_2026')->value('id');
        $igcseId = DB::table('courses')->where('code', 'CAIE_IGCSE_2026')->value('id');
        $boardId = DB::table('exam_boards')->where('code', 'CAIE')->value('id');
        $studentRows = [
            ['student.platform@preppy.test', 'platform', null, $oLevelId, 'Year 10', 'Beacon School', ['5054','4024']],
            ['student.institute@preppy.test', 'institute', $instituteId, $oLevelId, 'Year 10', 'Preppy Demo Academy', ['5054','4024','5070']],
            ['student.institute2@preppy.test', 'institute', $instituteId, $igcseId, 'Year 11', 'Preppy Demo Academy', ['5054','5090']],
            ['student.upgrade@preppy.test', 'platform', null, $oLevelId, 'Year 10', 'City School', ['5054','4024','5070']],
            ['student.downgrade@preppy.test', 'platform', null, $oLevelId, 'Year 10', 'City School', ['5054','4024','5070']],
            ['student.renewal@preppy.test', 'platform', null, $oLevelId, 'Year 11', 'City School', ['5054','4024']],
            ['google.student@preppy.test', 'platform', null, $oLevelId, 'Year 10', 'Google Demo School', ['5054','4024']],
            ['apple.student@preppy.test', 'platform', null, $igcseId, 'Year 10', 'Apple Demo School', ['5054','5090']],
        ];
        foreach ($studentRows as [$email, $ownership, $studentInstituteId, $courseId, $schoolYear, $schoolName, $subjectCodes]) {
            $userId = $this->uid($email);
            DB::table('student_profiles')->updateOrInsert(['user_id' => $userId], [
                'ownership_type' => $ownership, 'institute_id' => $studentInstituteId, 'course_id' => $courseId,
                'exam_board_id' => $boardId, 'school_year' => $schoolYear, 'school_name' => $schoolName,
                'date_of_birth' => '2010-04-12', 'status' => 'active', 'onboarded_at' => $now,
                'preferences' => json_encode(['study_goal' => 'A grade', 'daily_minutes' => 45, 'learning_path_complete' => true]),
                'metadata' => json_encode(['seeded' => true, 'platform' => 'Preppy']),
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
            ]);
            $profileId = DB::table('student_profiles')->where('user_id', $userId)->value('id');
            foreach ($subjectCodes as $subjectCode) {
                $subjectId = DB::table('subjects')->where('code', $subjectCode)->where('syllabus_year_start', 2026)->value('id');
                DB::table('student_subjects')->updateOrInsert(
                    ['student_profile_id' => $profileId, 'subject_id' => $subjectId],
                    ['status' => 'active', 'started_at' => $now, 'ended_at' => null, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        // google.new@preppy.test and apple.new@preppy.test intentionally have no profile/subjects.
        // They exercise first-time social onboarding -> Basic Information -> Learning Path -> Plan Activation.

        $parentId = $this->uid('parent@preppy.test');
        $parent2Id = $this->uid('parent2@preppy.test');
        foreach ([
            [$parentId, $this->uid('student.platform@preppy.test'), 'Father'],
            [$parentId, $this->uid('student.upgrade@preppy.test'), 'Father'],
            [$parent2Id, $this->uid('student.institute@preppy.test'), 'Mother'],
        ] as [$pid, $sid, $relationship]) {
            DB::table('parent_student_links')->updateOrInsert(
                ['parent_user_id' => $pid, 'student_user_id' => $sid],
                ['relationship' => $relationship, 'status' => 'active', 'can_view_performance' => true, 'can_view_subscription' => true, 'invited_by' => $sid, 'accepted_at' => $now, 'invite_token_hash' => null, 'invite_expires_at' => null, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $this->seedInvitations($instituteId, $instituteAdminId, $adminId, $now);
        $this->seedEducatorAccess($instituteId, $adminId, $now);

        DB::table('guest_demo_sessions')->updateOrInsert(
            ['id' => '11111111-1111-4111-8111-111111111111'],
            ['email' => 'guest@preppy.test', 'device_fingerprint' => 'preppy-demo-device', 'status' => 'active', 'started_at' => $now, 'expires_at' => $now->copy()->addDays(7), 'converted_at' => null, 'converted_user_id' => null, 'progress_snapshot' => json_encode(['subject_code' => '5054', 'questions_attempted' => 12, 'correct_answers' => 9]), 'metadata' => json_encode(['seeded' => true, 'platform' => 'Preppy']), 'created_at' => $now, 'updated_at' => $now]
        );
    }

    private function seedInvitations(int $instituteId, int $instituteAdminId, int $adminId, $now): void
    {
        $instituteToken = 'preppy-demo-institute-invite';
        DB::table('institute_invites')->updateOrInsert(['token_hash' => hash('sha256', $instituteToken)], [
            'institute_id' => $instituteId, 'email' => 'pending.institute.staff@preppy.test', 'role_slug' => 'institute_staff',
            'expires_at' => $now->copy()->addDays(7), 'accepted_at' => null, 'invited_by' => $instituteAdminId,
            'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now,
        ]);
        $instituteInviteId = DB::table('institute_invites')->where('token_hash', hash('sha256', $instituteToken))->value('id');
        $this->adminToken('institute', $instituteInviteId, $instituteToken, $now);

        foreach ([['teacher', 'pending.teacher@preppy.test', 'preppy-demo-teacher-invite'], ['tutor', 'pending.tutor@preppy.test', 'preppy-demo-tutor-invite']] as [$role, $email, $token]) {
            DB::table('educator_invites')->updateOrInsert(['token_hash' => hash('sha256', $token)], [
                'email' => $email, 'role_slug' => $role, 'institute_id' => $instituteId,
                'expires_at' => $now->copy()->addDays(7), 'accepted_at' => null, 'invited_by' => $adminId,
                'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now,
            ]);
            $id = DB::table('educator_invites')->where('token_hash', hash('sha256', $token))->value('id');
            $this->adminToken('educator', $id, $token, $now);
        }

        $pendingParent = $this->uid('parent2@preppy.test');
        $pendingStudent = $this->uid('student.institute2@preppy.test');
        $parentToken = 'preppy-demo-parent-invite';
        DB::table('parent_student_links')->updateOrInsert(
            ['parent_user_id' => $pendingParent, 'student_user_id' => $pendingStudent],
            ['relationship' => 'Mother', 'status' => 'pending', 'can_view_performance' => true, 'can_view_subscription' => true, 'invited_by' => $instituteAdminId, 'accepted_at' => null, 'invite_token_hash' => hash('sha256', $parentToken), 'invite_expires_at' => $now->copy()->addDays(7), 'created_at' => $now, 'updated_at' => $now]
        );
        $parentLinkId = DB::table('parent_student_links')->where(['parent_user_id' => $pendingParent, 'student_user_id' => $pendingStudent])->value('id');
        $this->adminToken('parent', $parentLinkId, $parentToken, $now);
    }

    private function seedEducatorAccess(int $instituteId, int $adminId, $now): void
    {
        $teacherId = $this->uid('teacher@preppy.test');
        $tutorId = $this->uid('tutor@preppy.test');
        $courseId = DB::table('courses')->where('code', 'CAIE_O_LEVEL_2026')->value('id');
        $physicsId = DB::table('subjects')->where('code', '5054')->where('syllabus_year_start', 2026)->value('id');

        DB::table('teaching_classes')->updateOrInsert(
            ['institute_id' => $instituteId, 'name' => 'Preppy O Level Physics - A'],
            ['course_id' => $courseId, 'subject_id' => $physicsId, 'teacher_user_id' => $teacherId, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]
        );
        $classId = DB::table('teaching_classes')->where(['institute_id' => $instituteId, 'name' => 'Preppy O Level Physics - A'])->value('id');
        foreach (['student.institute@preppy.test', 'student.institute2@preppy.test'] as $email) {
            DB::table('teaching_class_students')->updateOrInsert(['teaching_class_id' => $classId, 'student_user_id' => $this->uid($email)], ['created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('educator_access_grants')->updateOrInsert(
            ['educator_user_id' => $teacherId, 'scope_type' => 'class', 'scope_id' => $classId],
            ['institute_id' => $instituteId, 'status' => 'active', 'can_manage_parents' => true, 'granted_by' => $adminId, 'revoked_at' => null, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('educator_access_grants')->updateOrInsert(
            ['educator_user_id' => $tutorId, 'scope_type' => 'subject', 'scope_id' => $physicsId],
            ['institute_id' => null, 'status' => 'active', 'can_manage_parents' => false, 'granted_by' => $adminId, 'revoked_at' => null, 'created_at' => $now, 'updated_at' => $now]
        );
    }

    private function adminToken(string $type, int $id, string $token, $now): void
    {
        DB::table('admin_invitation_tokens')->updateOrInsert(
            ['invitation_type' => $type, 'invitation_id' => $id],
            ['encrypted_token' => Crypt::encryptString($token), 'created_at' => $now, 'updated_at' => $now]
        );
    }

    private function uid(string $email): int
    {
        return (int) DB::table('users')->where('email', $email)->value('id');
    }
}
