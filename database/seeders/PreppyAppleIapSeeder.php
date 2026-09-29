<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreppyAppleIapSeeder extends Seeder
{
    public function run(): void
    {
        $now=now();
        $products=[
            ['PHYSICS_MONTHLY','com.preppy.physics.monthly','preppy_monthly'],
            ['PHYSICS_QUARTERLY','com.preppy.physics.quarterly','preppy_quarterly'],
            ['PHYSICS_YEARLY','com.preppy.physics.yearly','preppy_yearly'],
            ['OLEVEL_COMPLETE','com.preppy.olevel.complete.monthly','preppy_monthly'],
            ['ALL_SUBJECTS_PREMIUM','com.preppy.allsubjects.premium.monthly','preppy_monthly'],
        ];
        foreach($products as [$planCode,$productId,$group]){
            $planId=DB::table('plans')->where('code',$planCode)->value('id');
            DB::table('apple_iap_products')->updateOrInsert(['product_id'=>$productId],[
                'plan_id'=>$planId,'subscription_group_id'=>$group,'is_active'=>true,'metadata'=>json_encode(['seeded'=>true,'replace_with_app_store_connect_product'=>true]),'created_at'=>$now,'updated_at'=>$now,
            ]);
        }
    }
}
