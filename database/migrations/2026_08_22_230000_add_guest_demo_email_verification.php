<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_verification_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 190)->index();
            $table->string('purpose', 60)->index();
            $table->string('code_hash');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->timestampTz('expires_at');
            $table->timestampTz('resend_available_at');
            $table->timestampTz('verified_at')->nullable();
            $table->string('verification_grant_hash', 64)->nullable()->unique();
            $table->timestampTz('verification_grant_expires_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();

            $table->index(['email', 'purpose', 'created_at']);
        });

        Schema::table('guest_demo_sessions', function (Blueprint $table) {
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('access_token_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('guest_demo_sessions', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'access_token_hash']);
        });

        Schema::dropIfExists('email_verification_challenges');
    }
};
