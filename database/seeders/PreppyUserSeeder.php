<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PreppyUserSeeder extends Seeder
{
    public const PASSWORD = 'Password@123';

    public function run(): void
    {
        $now = now();
        $password = Hash::make(self::PASSWORD);

        $users = [
            ['Preppy System Administrator', 'admin@preppy.test', 'system_admin', 'active'],
            ['Preppy System Staff', 'staff@preppy.test', 'system_staff', 'active'],
            ['Preppy Teacher', 'teacher@preppy.test', 'teacher', 'active'],
            ['Preppy Tutor', 'tutor@preppy.test', 'tutor', 'active'],
            ['Preppy Institute Administrator', 'institute.admin@preppy.test', 'institute_admin', 'active'],
            ['Preppy Institute Staff', 'institute.staff@preppy.test', 'institute_staff', 'active'],
            ['Preppy Platform Student', 'student.platform@preppy.test', 'student', 'active'],
            ['Preppy Institute Student', 'student.institute@preppy.test', 'student', 'active'],
            ['Preppy Second Institute Student', 'student.institute2@preppy.test', 'student', 'active'],
            ['Preppy Upgrade Student', 'student.upgrade@preppy.test', 'student', 'active'],
            ['Preppy Downgrade Student', 'student.downgrade@preppy.test', 'student', 'active'],
            ['Preppy Renewal Student', 'student.renewal@preppy.test', 'student', 'active'],
            ['Preppy Google Student', 'google.student@preppy.test', 'student', 'active'],
            ['Preppy Apple Student', 'apple.student@preppy.test', 'student', 'active'],
            ['Preppy Google Onboarding', 'google.new@preppy.test', 'student', 'active'],
            ['Preppy Apple Onboarding', 'apple.new@preppy.test', 'student', 'active'],
            ['Preppy Parent', 'parent@preppy.test', 'parent', 'active'],
            ['Preppy Second Parent', 'parent2@preppy.test', 'parent', 'active'],
            ['Preppy Disabled User', 'disabled@preppy.test', 'student', 'disabled'],
        ];

        foreach ($users as [$name, $email, $role, $status]) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'status' => $status,
                    'email_verified_at' => $now,
                    'last_login_at' => null,
                    'timezone' => 'Asia/Karachi',
                    'locale' => 'en',
                    'metadata' => json_encode(['seeded' => true, 'platform' => 'Preppy']),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );

            $userId = DB::table('users')->where('email', $email)->value('id');
            $roleId = DB::table('roles')->where('slug', $role)->value('id');
            DB::table('user_roles')->updateOrInsert(
                ['user_id' => $userId, 'role_id' => $roleId, 'context_type' => 'system', 'context_id' => null],
                ['starts_at' => $now, 'ends_at' => null, 'assigned_by' => null, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $social = [
            ['google.student@preppy.test', 'google', 'preppy-google-existing-001', 'google.student@preppy.test', 'https://example.test/avatar/google-existing.png'],
            ['google.new@preppy.test', 'google', 'preppy-google-onboarding-001', 'google.new@preppy.test', 'https://example.test/avatar/google-new.png'],
            ['apple.student@preppy.test', 'apple', '000999.preppy.apple.existing', 'apple.student@preppy.test', null],
            ['apple.new@preppy.test', 'apple', '000999.preppy.apple.onboarding', 'apple.new@preppy.test', null],
        ];
        foreach ($social as [$email, $provider, $providerUserId, $providerEmail, $avatar]) {
            $userId = DB::table('users')->where('email', $email)->value('id');
            DB::table('social_accounts')->updateOrInsert(
                ['provider' => $provider, 'provider_user_id' => $providerUserId],
                [
                    'user_id' => $userId,
                    'provider_email' => $providerEmail,
                    'avatar_url' => $avatar,
                    'access_token' => null,
                    'refresh_token' => null,
                    'token_expires_at' => null,
                    'metadata' => json_encode(['seeded' => true, 'platform' => 'Preppy']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
