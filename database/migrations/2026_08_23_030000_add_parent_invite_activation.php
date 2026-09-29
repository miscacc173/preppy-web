<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('parent_student_links', function (Blueprint $table): void {
            $table->string('invite_token_hash', 64)->nullable()->unique();
            $table->timestampTz('invite_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parent_student_links', function (Blueprint $table): void {
            $table->dropUnique(['invite_token_hash']);
            $table->dropColumn(['invite_token_hash', 'invite_expires_at']);
        });
    }
};
