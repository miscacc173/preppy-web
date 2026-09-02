<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestingEngagementSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $users = DB::table('users')->where('email', 'like', '%@preppy.test')->get(['id', 'email']);

        foreach ($users as $user) {
            DB::table('user_app_states')->updateOrInsert(
                ['user_id' => $user->id, 'key' => 'onboarding'],
                ['value' => json_encode(['completed' => true, 'seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
            DB::table('user_app_states')->updateOrInsert(
                ['user_id' => $user->id, 'key' => 'dashboard_preferences'],
                ['value' => json_encode(['compact' => false, 'seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $platformStudentId = DB::table('users')->where('email', 'student.platform@preppy.test')->value('id');
        DB::table('social_accounts')->updateOrInsert(
            ['provider' => 'google', 'provider_user_id' => 'seed-google-student-001'],
            ['user_id' => $platformStudentId, 'provider_email' => 'student.platform@preppy.test', 'avatar_url' => null, 'access_token' => null, 'refresh_token' => null, 'token_expires_at' => null, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        foreach ([
            ['SEED-LEARN-001', 'learn', 'Physics', 'Motion, Forces and Energy', 8, 10, 80, 'A', 720],
            ['SEED-EXAM-001', 'exam', 'Physics', null, 32, 40, 80, 'A', 2400],
        ] as [$uid, $type, $subject, $topic, $earned, $marks, $accuracy, $grade, $seconds]) {
            DB::table('learning_sessions')->updateOrInsert(
                ['session_uid' => $uid],
                ['user_id' => $platformStudentId, 'session_type' => $type, 'level' => 'O Level', 'subject' => $subject, 'topic' => $topic, 'earned' => $earned, 'total_marks' => $marks, 'accuracy' => $accuracy, 'grade' => $grade, 'time_seconds' => $seconds, 'average_seconds_per_question' => (int) ($seconds / max(1, $marks)), 'questions' => json_encode([['question_key' => 'Q1', 'correct' => true]]), 'metadata' => json_encode(['seeded' => true]), 'status' => 'completed', 'started_at' => $now->copy()->subDays(2)->subMinutes(30), 'completed_at' => $now->copy()->subDays(2), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('contact_messages')->updateOrInsert(
            ['email' => 'student.platform@preppy.test', 'subject' => 'Seeded support question'],
            ['user_id' => $platformStudentId, 'kind' => 'support', 'name' => 'Platform Student', 'category' => 'subscription', 'message' => 'This is seeded support data for admin testing.', 'status' => 'open', 'created_at' => $now, 'updated_at' => $now]
        );

        $challengeId = '22222222-2222-4222-8222-222222222222';
        DB::table('email_verification_challenges')->updateOrInsert(
            ['id' => $challengeId],
            ['email' => 'verification.demo@preppy.test', 'purpose' => 'guest_demo', 'code_hash' => password_hash('123456', PASSWORD_BCRYPT), 'attempts' => 0, 'max_attempts' => 5, 'expires_at' => $now->copy()->addMinutes(15), 'resend_available_at' => $now, 'verified_at' => null, 'verification_grant_hash' => null, 'verification_grant_expires_at' => null, 'consumed_at' => null, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('guest_demo_email_claims')->updateOrInsert(
            ['email' => 'guest@preppy.test'],
            ['guest_demo_session_id' => '11111111-1111-4111-8111-111111111111', 'claimed_at' => $now, 'created_at' => $now, 'updated_at' => $now]
        );
    }
}
