<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(['slug' => 'teacher'], [
            'name' => 'Teacher', 'scope' => 'system', 'is_system' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $roleId = DB::table('roles')->where('slug', 'teacher')->value('id');
        foreach (['students.view'] as $permission) {
            $permissionId = DB::table('permissions')->where('slug', $permission)->value('id');
            if ($permissionId) DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'teacher')->value('id');
        if ($roleId) {
            DB::table('user_roles')->where('role_id', $roleId)->delete();
            DB::table('role_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
