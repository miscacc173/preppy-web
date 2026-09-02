<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestingUserSeeder extends Seeder
{
    public const PASSWORD = 'Password@123';

    public function run(): void
    {
        $now = now();
        $password = Hash::make(self::PASSWORD);

        // One login for every role supported by the application, plus a few
        // additional students/parents needed to exercise relationship flows.
        $users = [
            ['System Administrator', 'admin@preppy.test', 'system_admin'],
            ['System Staff', 'staff@preppy.test', 'system_staff'],
            ['Platform Tutor', 'tutor@preppy.test', 'tutor'],
            ['Institute Teacher', 'teacher@preppy.test', 'teacher'],
            ['Institute Administrator', 'institute.admin@preppy.test', 'institute_admin'],
            ['Institute Staff', 'institute.staff@preppy.test', 'institute_staff'],
            ['Platform Student', 'student.platform@preppy.test', 'student'],
            ['Institute Student', 'student.institute@preppy.test', 'student'],
            ['Second Institute Student', 'student.institute2@preppy.test', 'student'],
            ['Demo Parent', 'parent@preppy.test', 'parent'],
            ['Second Parent', 'parent2@preppy.test', 'parent'],
        ];

        foreach ($users as [$name, $email, $role]) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'status' => 'active',
                    'email_verified_at' => $now,
                    'last_login_at' => $now->copy()->subHour(),
                    'timezone' => 'Asia/Karachi',
                    'locale' => 'en',
                    'metadata' => json_encode(['seeded' => true, 'test_login' => true]),
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
    }
}
