<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guest_demo_email_claims', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('email', 190)->unique();
            $table->uuid('guest_demo_session_id')->nullable()->unique();
            $table->timestampTz('claimed_at');
            $table->timestampsTz();
        });

        // Preserve all historical sessions while reserving each normalized
        // email exactly once for future guest-session eligibility checks.
        DB::statement(<<<'SQL'
            INSERT INTO guest_demo_email_claims (email, guest_demo_session_id, claimed_at, created_at, updated_at)
            SELECT DISTINCT ON (LOWER(TRIM(email)))
                LOWER(TRIM(email)), id, COALESCE(started_at, created_at), NOW(), NOW()
            FROM guest_demo_sessions
            WHERE email IS NOT NULL AND TRIM(email) <> ''
            ORDER BY LOWER(TRIM(email)), created_at ASC, id ASC
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_demo_email_claims');
    }
};
