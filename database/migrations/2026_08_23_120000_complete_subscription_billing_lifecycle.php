<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('checkout_idempotency_key', 100)->nullable()->unique()->after('provider_subscription_id');
            $table->timestampTz('grace_ends_at')->nullable()->after('trial_ends_at');
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->unsignedBigInteger('refunded_minor')->default(0)->after('paid_minor');
            $table->unsignedInteger('retry_count')->default(0)->after('refunded_minor');
            $table->timestampTz('next_retry_at')->nullable()->after('due_at')->index();
            $table->timestampTz('collection_ends_at')->nullable()->after('next_retry_at');
            $table->timestampTz('canceled_at')->nullable()->after('paid_at');
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('checkout_reference');
            $table->unsignedBigInteger('retry_of_payment_id')->nullable()->after('idempotency_key')->index();
            $table->unsignedInteger('attempt_number')->default(1)->after('retry_of_payment_id');
            $table->unsignedBigInteger('refunded_minor')->default(0)->after('amount_minor');
            $table->string('receipt_number', 100)->nullable()->unique()->after('currency');
            $table->timestampTz('receipt_issued_at')->nullable()->after('receipt_number');
            $table->timestampTz('expires_at')->nullable()->after('receipt_issued_at');
        });
        Schema::table('institute_commissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('reversed_amount_minor')->default(0)->after('commission_amount_minor');
        });

        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id')->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->string('idempotency_key', 100)->unique();
            $table->string('provider_refund_id', 190)->nullable()->unique();
            $table->string('status', 30)->default('processing')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable()->index();
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();
        });

        Schema::create('institute_commission_adjustments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('institute_commission_id')->index();
            $table->unsignedBigInteger('payment_refund_id')->index();
            $table->string('adjustment_type', 50);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 30)->default('applied')->index();
            $table->timestampTz('applied_at');
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();
            $table->unique(['institute_commission_id', 'payment_refund_id'], 'commission_refund_adjustment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_commission_adjustments');
        Schema::dropIfExists('payment_refunds');
        Schema::table('institute_commissions', fn (Blueprint $table) => $table->dropColumn('reversed_amount_minor'));
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropUnique(['receipt_number']);
            $table->dropIndex(['retry_of_payment_id']);
            $table->dropColumn(['idempotency_key', 'retry_of_payment_id', 'attempt_number', 'refunded_minor', 'receipt_number', 'receipt_issued_at', 'expires_at']);
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['next_retry_at']);
            $table->dropColumn(['refunded_minor', 'retry_count', 'next_retry_at', 'collection_ends_at', 'canceled_at']);
        });
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropUnique(['checkout_idempotency_key']);
            $table->dropColumn(['checkout_idempotency_key', 'grace_ends_at']);
        });
    }
};
