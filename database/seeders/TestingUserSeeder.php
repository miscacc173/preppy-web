<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestingUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('Password@123');

        $users = [
            ['name' => 'System Administrator', 'email' => 'admin@aistar.test', 'role' => 'system_admin'],
            ['name' => 'System Staff', 'email' => 'staff@aistar.test', 'role' => 'system_staff'],
            ['name' => 'Content Tutor', 'email' => 'tutor@aistar.test', 'role' => 'tutor'],
            ['name' => 'Institute Administrator', 'email' => 'institute.admin@aistar.test', 'role' => 'institute_admin'],
            ['name' => 'Institute Staff', 'email' => 'institute.staff@aistar.test', 'role' => 'institute_staff'],
            ['name' => 'Platform Student', 'email' => 'student.platform@aistar.test', 'role' => 'student'],
            ['name' => 'Institute Student', 'email' => 'student.institute@aistar.test', 'role' => 'student'],
            ['name' => 'Second Institute Student', 'email' => 'student.institute2@aistar.test', 'role' => 'student'],
            ['name' => 'Demo Parent', 'email' => 'parent@aistar.test', 'role' => 'parent'],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $password,
                    'status' => 'active',
                    'email_verified_at' => $now,
                    'timezone' => 'Asia/Karachi',
                    'locale' => 'en',
                    'metadata' => json_encode(['seeded' => true]),
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );

            $userId = DB::table('users')->where('email', $user['email'])->value('id');
            $roleId = DB::table('roles')->where('slug', $user['role'])->value('id');

            DB::table('user_roles')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'context_type' => 'system',
                    'context_id' => null,
                ],
                [
                    'starts_at' => $now,
                    'ends_at' => null,
                    'assigned_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
