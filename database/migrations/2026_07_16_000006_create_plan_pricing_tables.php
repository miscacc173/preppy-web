<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 150);
            $t->string('code', 100)->unique();
            $t->string('scope', 40)->index();
            $t->unsignedBigInteger('course_id')->nullable()->index();
            $t->string('billing_interval', 30)->default('month');
            $t->unsignedInteger('billing_interval_count')->default(1);
            $t->unsignedBigInteger('price_minor');
            $t->char('currency', 3)->default('GBP');
            $t->unsignedInteger('trial_days')->default(0);
            $t->boolean('is_public')->default(true);
            $t->boolean('is_active')->default(true);
            $t->jsonb('features')->default(DB::raw("'{}'::jsonb"));
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('plan_subjects', function (Blueprint $t) {
            $t->unsignedBigInteger('plan_id');
            $t->unsignedBigInteger('subject_id');
            $t->primary(['plan_id', 'subject_id']);
        });
        Schema::create('discounts', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 150);
            $t->string('code', 80)->nullable()->unique();
            $t->string('discount_type', 30);
            $t->unsignedBigInteger('amount_minor')->nullable();
            $t->decimal('percentage', 7, 4)->nullable();
            $t->unsignedBigInteger('institute_id')->nullable()->index();
            $t->unsignedBigInteger('plan_id')->nullable()->index();
            $t->timestampTz('starts_at')->nullable();
            $t->timestampTz('ends_at')->nullable();
            $t->unsignedInteger('max_redemptions')->nullable();
            $t->unsignedInteger('redemptions_count')->default(0);
            $t->boolean('is_active')->default(true);
            $t->jsonb('rules')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('institute_plan_prices', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('institute_id')->index();
            $t->unsignedBigInteger('plan_id')->index();
            $t->unsignedBigInteger('price_minor');
            $t->char('currency', 3)->default('GBP');
            $t->timestampTz('starts_at')->nullable();
            $t->timestampTz('ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->unique(['institute_id', 'plan_id', 'starts_at'], 'institute_plan_price_unique');
        });
        DB::statement("ALTER TABLE plans ADD CONSTRAINT plans_scope_check CHECK (scope IN ('subject','multi_subject','course','all_subjects','institute'))");
        DB::statement("ALTER TABLE discounts ADD CONSTRAINT discounts_value_check CHECK ((discount_type='fixed' AND amount_minor IS NOT NULL AND percentage IS NULL) OR (discount_type='percentage' AND percentage IS NOT NULL AND amount_minor IS NULL))");
    }

    public function down(): void
    {
        foreach (['institute_plan_prices', 'discounts', 'plan_subjects', 'plans'] as $x) Schema::dropIfExists($x);
    }
};