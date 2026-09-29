<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreppyNotificationAuditSeeder extends Seeder
{
    public function run(): void
    {
        $now=now(); $adminId=$this->uid('admin@preppy.test');
        foreach(['student.platform@preppy.test','student.institute@preppy.test','parent@preppy.test','teacher@preppy.test'] as $email){
            $uid=$this->uid($email);
            foreach([['study_reminder','push',true],['performance_summary','email',true],['subscription_updates','email',true],['marketing_promotions','push',false]] as [$type,$channel,$enabled]){
                DB::table('notification_preferences')->updateOrInsert(['user_id'=>$uid,'notification_type'=>$type,'channel'=>$channel],[
                    'is_enabled'=>$enabled,'schedule'=>json_encode($type==='study_reminder'?['time'=>'19:00','days'=>['mon','tue','wed','thu','fri']]:[]),'created_at'=>$now,'updated_at'=>$now,
                ]);
            }
            DB::table('user_devices')->updateOrInsert(['user_id'=>$uid,'device_id'=>'preppy-seed-device-'.$uid],[
                'platform'=>'android','push_token'=>null,'timezone'=>'Asia/Karachi','last_seen_at'=>$now,'is_active'=>false,'metadata'=>json_encode(['app_version'=>'1.0.0','seeded'=>true]),'created_at'=>$now,'updated_at'=>$now,
            ]);
        }
        // Avoid seeding learning sessions for first-time Google/Apple onboarding users.
        foreach([
            ['student.platform@preppy.test','PREPPY-SESS-001','exam','O Level','Physics','Waves',32,40,80,'A',2400,120],
            ['student.institute@preppy.test','PREPPY-SESS-002','learn','O Level','Mathematics','Algebra',18,20,90,'A*',1200,60],
        ] as [$email,$uid,$type,$level,$subject,$topic,$earned,$total,$accuracy,$grade,$seconds,$avg]){
            DB::table('learning_sessions')->updateOrInsert(['session_uid'=>$uid],[
                'user_id'=>$this->uid($email),'session_type'=>$type,'level'=>$level,'subject'=>$subject,'topic'=>$topic,'earned'=>$earned,'total_marks'=>$total,'accuracy'=>$accuracy,'grade'=>$grade,
                'time_seconds'=>$seconds,'average_seconds_per_question'=>$avg,'questions'=>json_encode([]),'metadata'=>json_encode(['seeded'=>true]),'status'=>'completed','started_at'=>$now->copy()->subDays(5)->subHour(),'completed_at'=>$now->copy()->subDays(5),'created_at'=>$now,'updated_at'=>$now,
            ]);
        }
        DB::table('audit_logs')->insert([
            'actor_user_id'=>$adminId,'action'=>'preppy.demo_data.seeded','auditable_type'=>'system','auditable_id'=>null,'ip_address'=>'127.0.0.1','user_agent'=>'Laravel Seeder',
            'before'=>json_encode([]),'after'=>json_encode(['platform'=>'Preppy','status'=>'ready']),'metadata'=>json_encode(['seeded'=>true]),'created_at'=>$now,
        ]);
    }
    private function uid(string $email): int { return (int)DB::table('users')->where('email',$email)->value('id'); }
}
