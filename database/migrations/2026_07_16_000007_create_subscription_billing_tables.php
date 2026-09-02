<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('subscriber_type', 30)->index();
            $t->unsignedBigInteger('subscriber_id')->index();
            $t->unsignedBigInteger('plan_id')->index();
            $t->unsignedBigInteger('institute_id')->nullable()->index();
            $t->string('status', 30)->index();
            $t->string('provider', 40)->nullable();
            $t->string('provider_subscription_id', 190)->nullable()->unique();
            $t->timestampTz('trial_ends_at')->nullable();
            $t->timestampTz('current_period_starts_at');
            $t->timestampTz('current_period_ends_at');
            $t->boolean('cancel_at_period_end')->default(false);
            $t->timestampTz('canceled_at')->nullable();
            $t->timestampTz('ended_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('subscription_items', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('subscription_id')->index();
            $t->unsignedBigInteger('plan_id')->index();
            $t->unsignedBigInteger('subject_id')->nullable()->index();
            $t->unsignedInteger('quantity')->default(1);
            $t->unsignedBigInteger('unit_price_minor');
            $t->char('currency', 3);
            $t->jsonb('entitlements')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('invoice_number', 80)->unique();
            $t->unsignedBigInteger('subscription_id')->nullable()->index();
            $t->string('billable_type', 30)->index();
            $t->unsignedBigInteger('billable_id')->index();
            $t->string('status', 30)->default('draft')->index();
            $t->unsignedBigInteger('subtotal_minor')->default(0);
            $t->unsignedBigInteger('discount_minor')->default(0);
            $t->unsignedBigInteger('tax_minor')->default(0);
            $t->unsignedBigInteger('total_minor')->default(0);
            $t->unsignedBigInteger('paid_minor')->default(0);
            $t->char('currency', 3);
            $t->timestampTz('issued_at')->nullable();
            $t->timestampTz('due_at')->nullable();
            $t->timestampTz('paid_at')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('invoice_id')->index();
            $t->string('description', 255);
            $t->unsignedInteger('quantity')->default(1);
            $t->unsignedBigInteger('unit_price_minor');
            $t->unsignedBigInteger('line_total_minor');
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('invoice_id')->nullable()->index();
            $t->string('payer_type', 30);
            $t->unsignedBigInteger('payer_id');
            $t->string('provider', 40)->nullable();
            $t->string('provider_payment_id', 190)->nullable()->unique();
            $t->string('status', 30)->index();
            $t->unsignedBigInteger('amount_minor');
            $t->char('currency', 3);
            $t->timestampTz('paid_at')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscription_status_check CHECK (status IN ('trialing','active','past_due','paused','canceled','expired'))");
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscription_subscriber_check CHECK (subscriber_type IN ('student','institute'))");
        DB::statement("CREATE UNIQUE INDEX one_live_student_subscription_idx ON subscriptions(subscriber_id) WHERE subscriber_type='student' AND status IN ('trialing','active','past_due','paused')");
    }

    public function down(): void
    {
        foreach (['payments', 'invoice_items', 'invoices', 'subscription_items', 'subscriptions'] as $x) Schema::dropIfExists($x);
    }
};