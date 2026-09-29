<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('actor_user_id')->nullable()->index();
            $t->string('action', 120)->index();
            $t->string('auditable_type', 120)->nullable()->index();
            $t->unsignedBigInteger('auditable_id')->nullable()->index();
            $t->string('ip_address', 64)->nullable();
            $t->text('user_agent')->nullable();
            $t->jsonb('before')->default(DB::raw("'{}'::jsonb"));
            $t->jsonb('after')->default(DB::raw("'{}'::jsonb"));
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};