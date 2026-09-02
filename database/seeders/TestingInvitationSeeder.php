<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TestingInvitationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $instituteInvite = DB::table('institute_invites')->where('email', 'new.staff@preppy.test')->first();
        $educatorInvite = DB::table('educator_invites')->where('email', 'pending.teacher@preppy.test')->first();

        foreach ([
            ['institute', $instituteInvite?->id, 'demo-institute-invite-token'],
            ['educator', $educatorInvite?->id, 'demo-educator-invite-token'],
        ] as [$type, $id, $token]) {
            if (!$id) continue;
            DB::table('admin_invitation_tokens')->updateOrInsert(
                ['invitation_type' => $type, 'invitation_id' => $id],
                ['encrypted_token' => Crypt::encryptString($token), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $parentId = DB::table('users')->where('email', 'parent2@preppy.test')->value('id');
        $studentId = DB::table('users')->where('email', 'student.institute@preppy.test')->value('id');
        $token = 'demo-parent-invite-token';
        DB::table('parent_student_links')->updateOrInsert(
            ['parent_user_id' => $parentId, 'student_user_id' => $studentId],
            ['relationship' => 'Guardian', 'status' => 'pending', 'can_view_performance' => true, 'can_view_subscription' => false, 'invited_by' => $studentId, 'accepted_at' => null, 'invite_token_hash' => hash('sha256', $token), 'invite_expires_at' => $now->copy()->addDays(7), 'created_at' => $now, 'updated_at' => $now]
        );
        $linkId = DB::table('parent_student_links')->where('parent_user_id', $parentId)->where('student_user_id', $studentId)->value('id');
        DB::table('admin_invitation_tokens')->updateOrInsert(
            ['invitation_type' => 'parent', 'invitation_id' => $linkId],
            ['encrypted_token' => Crypt::encryptString($token), 'created_at' => $now, 'updated_at' => $now]
        );
    }
}
