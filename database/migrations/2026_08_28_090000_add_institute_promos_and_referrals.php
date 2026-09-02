<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institute_promo_codes', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('institute_id')->index();
            $t->string('name', 150);
            $t->string('code', 80)->unique();
            $t->string('discount_type', 30);
            $t->unsignedBigInteger('amount_minor')->nullable();
            $t->decimal('percentage', 7, 4)->nullable();
            $t->timestampTz('starts_at')->nullable();
            $t->timestampTz('ends_at')->nullable();
            $t->unsignedInteger('max_redemptions')->nullable();
            $t->unsignedInteger('per_user_limit')->default(1);
            $t->unsignedInteger('redemptions_count')->default(0);
            $t->boolean('is_active')->default(true);
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });

        Schema::create('institute_promo_code_plans', function (Blueprint $t) {
            $t->unsignedBigInteger('institute_promo_code_id');
            $t->unsignedBigInteger('plan_id');
            $t->primary(['institute_promo_code_id', 'plan_id'], 'institute_promo_plan_pk');
            $t->index('plan_id');
        });

        Schema::create('referral_codes', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->unique();
            $t->string('code', 32)->unique();
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
        });

        Schema::create('referrals', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('referrer_user_id')->index();
            $t->unsignedBigInteger('referred_user_id')->unique();
            $t->unsignedBigInteger('referral_code_id')->index();
            $t->unsignedBigInteger('subscription_id')->nullable()->index();
            $t->unsignedBigInteger('invoice_id')->nullable()->index();
            $t->unsignedBigInteger('qualifying_payment_id')->nullable()->unique();
            $t->unsignedBigInteger('reward_discount_id')->nullable()->index();
            $t->string('status', 30)->default('pending')->index();
            $t->timestampTz('qualified_at')->nullable();
            $t->timestampTz('rewarded_at')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });

        Schema::create('promotion_redemptions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('promotion_type', 40)->index();
            $t->unsignedBigInteger('promotion_id')->index();
            $t->string('code', 80)->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('subscription_id')->nullable()->index();
            $t->unsignedBigInteger('invoice_id')->unique();
            $t->unsignedBigInteger('payment_id')->nullable()->unique();
            $t->unsignedBigInteger('discount_minor');
            $t->string('status', 30)->default('pending')->index();
            $t->timestampTz('expires_at')->nullable();
            $t->timestampTz('consumed_at')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->index(['promotion_type', 'promotion_id', 'status'], 'promotion_redemption_source_status_idx');
        });

        DB::statement("ALTER TABLE institute_promo_codes ADD CONSTRAINT institute_promo_value_check CHECK ((discount_type='fixed' AND amount_minor IS NOT NULL AND percentage IS NULL) OR (discount_type='percentage' AND percentage IS NOT NULL AND amount_minor IS NULL))");
        DB::statement("ALTER TABLE referrals ADD CONSTRAINT referrals_status_check CHECK (status IN ('pending','qualified','rewarded','cancelled'))");
        DB::statement("ALTER TABLE promotion_redemptions ADD CONSTRAINT promotion_redemptions_status_check CHECK (status IN ('pending','consumed','cancelled'))");
        DB::statement("ALTER TABLE promotion_redemptions ADD CONSTRAINT promotion_redemptions_type_check CHECK (promotion_type IN ('discount','institute_promo'))");
    }

    public function down(): void
    {
        foreach (['promotion_redemptions', 'referrals', 'referral_codes', 'institute_promo_code_plans', 'institute_promo_codes'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
