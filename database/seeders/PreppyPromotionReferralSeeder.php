<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreppyPromotionReferralSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $instituteId = DB::table('institutes')->where('code','PREPPY-DEMO-001')->value('id');
        DB::table('institute_promo_codes')->updateOrInsert(['code'=>'SCHOOL20'], [
            'institute_id'=>$instituteId,'name'=>'Preppy Demo Academy 20% Off','discount_type'=>'percentage','amount_minor'=>null,'percentage'=>20.0000,
            'starts_at'=>$now->copy()->subDay(),'ends_at'=>$now->copy()->addMonths(3),'max_redemptions'=>100,'per_user_limit'=>1,'redemptions_count'=>1,'is_active'=>true,
            'metadata'=>json_encode(['seeded'=>true,'platform'=>'Preppy']),'created_at'=>$now,'updated_at'=>$now,
        ]);
        $promoId = DB::table('institute_promo_codes')->where('code','SCHOOL20')->value('id');
        foreach (['INSTITUTE_CAMPUS','OLEVEL_COMPLETE'] as $code) {
            DB::table('institute_promo_code_plans')->insertOrIgnore(['institute_promo_code_id'=>$promoId,'plan_id'=>$this->plan($code)]);
        }

        foreach (['student.platform@preppy.test','student.institute@preppy.test','student.upgrade@preppy.test','student.downgrade@preppy.test'] as $email) {
            $uid=$this->uid($email); $code='REF'.str_pad((string)$uid,6,'0',STR_PAD_LEFT);
            DB::table('referral_codes')->updateOrInsert(['user_id'=>$uid],['code'=>$code,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]);
        }

        $referrer=$this->uid('student.platform@preppy.test');
        $referred=$this->uid('student.institute@preppy.test');
        $refCodeId=DB::table('referral_codes')->where('user_id',$referrer)->value('id');
        $subscriptionId=DB::table('subscriptions')->where('subscriber_id',$referred)->where('subscriber_type','student')->where('status','active')->value('id');
        $invoiceId=DB::table('invoices')->where('subscription_id',$subscriptionId)->where('status','paid')->value('id');
        $paymentId=DB::table('payments')->where('invoice_id',$invoiceId)->where('status','succeeded')->value('id');

        $rewardCode='FRIEND10-'.$referrer;
        DB::table('discounts')->updateOrInsert(['code'=>$rewardCode], [
            'name'=>'Preppy Referral Reward','discount_type'=>'percentage','amount_minor'=>null,'percentage'=>10.0000,'institute_id'=>null,'plan_id'=>null,
            'starts_at'=>$now->copy()->subDay(),'ends_at'=>$now->copy()->addDays(30),'max_redemptions'=>1,'redemptions_count'=>0,'is_active'=>true,
            'rules'=>json_encode(['referral_reward'=>true,'restricted_user_id'=>$referrer,'seeded'=>true]),'created_at'=>$now,'updated_at'=>$now,
        ]);
        $rewardDiscountId=DB::table('discounts')->where('code',$rewardCode)->value('id');
        DB::table('referrals')->updateOrInsert(['referred_user_id'=>$referred], [
            'referrer_user_id'=>$referrer,'referral_code_id'=>$refCodeId,'subscription_id'=>$subscriptionId,'invoice_id'=>$invoiceId,'qualifying_payment_id'=>$paymentId,
            'reward_discount_id'=>$rewardDiscountId,'status'=>'rewarded','qualified_at'=>$now->copy()->subDay(),'rewarded_at'=>$now->copy()->subDay(),
            'metadata'=>json_encode(['seeded'=>true,'platform'=>'Preppy']),'created_at'=>$now,'updated_at'=>$now,
        ]);

        // Successful institute promo redemption, useful for institute/admin reporting.
        DB::table('promotion_redemptions')->updateOrInsert(['invoice_id'=>$invoiceId], [
            'promotion_type'=>'institute_promo','promotion_id'=>$promoId,'code'=>'SCHOOL20','user_id'=>$referred,'subscription_id'=>$subscriptionId,'payment_id'=>$paymentId,
            'discount_minor'=>49980,'status'=>'consumed','expires_at'=>null,'consumed_at'=>$now->copy()->subDay(),'metadata'=>json_encode(['seeded'=>true]),'created_at'=>$now,'updated_at'=>$now,
        ]);
    }
    private function uid(string $email): int { return (int)DB::table('users')->where('email',$email)->value('id'); }
    private function plan(string $code): int { return (int)DB::table('plans')->where('code',$code)->value('id'); }
}
