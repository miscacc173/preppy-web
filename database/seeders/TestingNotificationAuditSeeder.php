<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingNotificationAuditSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@preppy.test')->value('id');

        foreach (DB::table('users')->where('email', 'like', '%@preppy.test')->pluck('email') as $email) {
            $userId = DB::table('users')->where('email', $email)->value('id');
            foreach ([
                ['study_reminder', 'push', true],
                ['performance_summary', 'email', true],
                ['subscription_updates', 'email', true],
                ['marketing', 'push', false],
            ] as [$type, $channel, $enabled]) {
                DB::table('notification_preferences')->updateOrInsert(
                    ['user_id' => $userId, 'notification_type' => $type, 'channel' => $channel],
                    ['is_enabled' => $enabled, 'schedule' => json_encode($type === 'study_reminder' ? ['time' => '19:00', 'days' => ['mon', 'tue', 'wed', 'thu', 'fri']] : []), 'created_at' => $now, 'updated_at' => $now]
                );
            }

            DB::table('user_devices')->updateOrInsert(
                ['user_id' => $userId, 'device_id' => 'seed-device-' . $userId],
                ['platform' => 'android', 'push_token' => 'seed-push-token-' . $userId, 'timezone' => 'Asia/Karachi', 'last_seen_at' => $now, 'is_active' => true, 'metadata' => json_encode(['app_version' => '1.0.0']), 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('audit_logs')->insert([
            ['actor_user_id' => $adminId, 'action' => 'testing_data.seeded', 'auditable_type' => 'system', 'auditable_id' => null, 'ip_address' => '127.0.0.1', 'user_agent' => 'Laravel Seeder', 'before' => json_encode([]), 'after' => json_encode(['status' => 'ready']), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now],
            ['actor_user_id' => $adminId, 'action' => 'institute.created', 'auditable_type' => 'Institute', 'auditable_id' => DB::table('institutes')->where('code', 'PREPPY-DEMO-001')->value('id'), 'ip_address' => '127.0.0.1', 'user_agent' => 'Laravel Seeder', 'before' => json_encode([]), 'after' => json_encode(['code' => 'PREPPY-DEMO-001']), 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now],
        ]);
    }
}
