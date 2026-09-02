<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [['System Admin', 'system_admin', 'system'], ['System Staff', 'system_staff', 'system'], ['Tutor', 'tutor', 'system'], ['Teacher', 'teacher', 'system'], ['Student', 'student', 'system'], ['Parent', 'parent', 'system'], ['Institute Admin', 'institute_admin', 'institute'], ['Institute Staff', 'institute_staff', 'institute']];
        foreach ($roles as [$name, $slug, $scope]) DB::table('roles')->updateOrInsert(['slug' => $slug], ['name' => $name, 'scope' => $scope, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()]);
        $permissions = ['users.manage', 'roles.manage', 'institutes.manage', 'institutes.students.manage', 'students.view', 'students.manage', 'parents.performance.view', 'subscriptions.manage', 'plans.manage', 'commissions.manage', 'catalog.manage', 'audit.view', 'content.review', 'content.approve'];
        foreach ($permissions as $p) DB::table('permissions')->updateOrInsert(['slug' => $p], ['name' => ucwords(str_replace(['.', '_'], ' ', $p)), 'group' => strtok($p, '.'), 'created_at' => now(), 'updated_at' => now()]);
        $map = ['system_admin' => $permissions, 'system_staff' => ['users.manage', 'institutes.manage', 'students.view', 'students.manage', 'subscriptions.manage', 'plans.manage', 'catalog.manage', 'audit.view'], 'tutor' => ['content.review', 'content.approve'], 'teacher' => ['students.view'], 'institute_admin' => ['institutes.students.manage', 'students.view', 'subscriptions.manage'], 'institute_staff' => ['institutes.students.manage', 'students.view'], 'parent' => ['parents.performance.view']];
        foreach ($map as $roleSlug => $perms) {
            $rid = DB::table('roles')->where('slug', $roleSlug)->value('id');
            foreach ($perms as $p) {
                $pid = DB::table('permissions')->where('slug', $p)->value('id');
                DB::table('role_permissions')->updateOrInsert(['role_id' => $rid, 'permission_id' => $pid]);
            }
        }
    }
}
