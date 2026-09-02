<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('educator_access_grants', function (Blueprint $table): void {
            $table->boolean('can_manage_parents')->default(false)->after('scope_id');
        });

        Schema::table('institute_commission_rules', function (Blueprint $table): void {
            $table->string('direction', 40)->default('platform_pays_institute')->after('commission_type');
        });
        Schema::table('institute_commissions', function (Blueprint $table): void {
            $table->string('direction', 40)->default('platform_pays_institute')->after('commission_rule_id')->index();
            $table->timestampTz('settled_at')->nullable()->after('paid_at');
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->timestampTz('period_starts_at')->nullable()->after('currency');
            $table->timestampTz('period_ends_at')->nullable()->after('period_starts_at');
            $table->unique(['subscription_id', 'period_starts_at'], 'subscription_invoice_period_unique');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('checkout_reference', 100)->nullable()->unique()->after('provider');
            $table->string('failure_code', 100)->nullable()->after('status');
            $table->text('failure_message')->nullable()->after('failure_code');
        });

        DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscription_status_check');
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscription_status_check CHECK (status IN ('incomplete','trialing','active','past_due','paused','canceled','expired'))");
        DB::statement('DROP INDEX IF EXISTS one_live_student_subscription_idx');
        DB::statement("CREATE UNIQUE INDEX one_live_student_subscription_idx ON subscriptions(subscriber_id) WHERE subscriber_type='student' AND status IN ('incomplete','trialing','active','past_due','paused')");
        DB::statement("ALTER TABLE institute_commission_rules ADD CONSTRAINT commission_direction_check CHECK (direction IN ('platform_pays_institute','platform_collects_from_institute'))");
        DB::statement("ALTER TABLE institute_commissions ADD CONSTRAINT commission_entry_direction_check CHECK (direction IN ('platform_pays_institute','platform_collects_from_institute'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE institute_commissions DROP CONSTRAINT IF EXISTS commission_entry_direction_check');
        DB::statement('ALTER TABLE institute_commission_rules DROP CONSTRAINT IF EXISTS commission_direction_check');
        DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscription_status_check');
        DB::table('subscriptions')->where('status', 'incomplete')->update(['status' => 'canceled']);
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscription_status_check CHECK (status IN ('trialing','active','past_due','paused','canceled','expired'))");
        DB::statement('DROP INDEX IF EXISTS one_live_student_subscription_idx');
        DB::statement("CREATE UNIQUE INDEX one_live_student_subscription_idx ON subscriptions(subscriber_id) WHERE subscriber_type='student' AND status IN ('trialing','active','past_due','paused')");
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn(['checkout_reference', 'failure_code', 'failure_message']));
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique('subscription_invoice_period_unique');
            $table->dropColumn(['period_starts_at', 'period_ends_at']);
        });
        Schema::table('institute_commissions', fn (Blueprint $table) => $table->dropColumn(['direction', 'settled_at']));
        Schema::table('institute_commission_rules', fn (Blueprint $table) => $table->dropColumn('direction'));
        Schema::table('educator_access_grants', fn (Blueprint $table) => $table->dropColumn('can_manage_parents'));
    }
};
