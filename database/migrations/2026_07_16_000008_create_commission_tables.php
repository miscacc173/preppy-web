<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institute_commission_rules', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('institute_id')->index();
            $t->unsignedBigInteger('plan_id')->nullable()->index();
            $t->string('commission_type', 30);
            $t->unsignedBigInteger('fixed_amount_minor')->nullable();
            $t->decimal('percentage', 7, 4)->nullable();
            $t->char('currency', 3)->default('GBP');
            $t->timestampTz('starts_at')->nullable();
            $t->timestampTz('ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('institute_commissions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('institute_id')->index();
            $t->unsignedBigInteger('student_user_id')->index();
            $t->unsignedBigInteger('subscription_id')->index();
            $t->unsignedBigInteger('payment_id')->nullable()->index();
            $t->unsignedBigInteger('commission_rule_id')->nullable()->index();
            $t->string('status', 30)->default('pending')->index();
            $t->unsignedBigInteger('base_amount_minor');
            $t->unsignedBigInteger('commission_amount_minor');
            $t->char('currency', 3);
            $t->timestampTz('earned_at');
            $t->timestampTz('available_at')->nullable();
            $t->timestampTz('paid_at')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('commission_payouts', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('institute_id')->index();
            $t->string('reference', 100)->unique();
            $t->string('status', 30)->default('pending')->index();
            $t->unsignedBigInteger('amount_minor');
            $t->char('currency', 3);
            $t->timestampTz('period_starts_at')->nullable();
            $t->timestampTz('period_ends_at')->nullable();
            $t->timestampTz('paid_at')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('commission_payout_items', function (Blueprint $t) {
            $t->unsignedBigInteger('commission_payout_id');
            $t->unsignedBigInteger('institute_commission_id');
            $t->primary(['commission_payout_id', 'institute_commission_id']);
        });
        DB::statement("ALTER TABLE institute_commission_rules ADD CONSTRAINT commission_rule_value_check CHECK ((commission_type='fixed' AND fixed_amount_minor IS NOT NULL AND percentage IS NULL) OR (commission_type='percentage' AND percentage IS NOT NULL AND fixed_amount_minor IS NULL))");
    }

    public function down(): void
    {
        foreach (['commission_payout_items', 'commission_payouts', 'institute_commissions', 'institute_commission_rules'] as $x) Schema::dropIfExists($x);
    }
};