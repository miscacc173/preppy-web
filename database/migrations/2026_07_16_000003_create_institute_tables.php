<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institutes', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 180);
            $t->string('slug', 180)->unique();
            $t->string('code', 60)->unique();
            $t->string('status', 30)->default('active')->index();
            $t->string('billing_email', 190)->nullable();
            $t->string('phone', 40)->nullable();
            $t->text('address')->nullable();
            $t->string('city', 100)->nullable();
            $t->string('country_code', 2)->nullable();
            $t->string('timezone', 64)->default('UTC');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->jsonb('settings')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->softDeletesTz();
        });
        Schema::create('institute_users', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('institute_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('position', 120)->nullable();
            $t->string('status', 30)->default('active')->index();
            $t->unsignedBigInteger('invited_by')->nullable();
            $t->timestampTz('joined_at')->nullable();
            $t->timestampsTz();
            $t->unique(['institute_id', 'user_id']);
        });
        Schema::create('institute_invites', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('institute_id')->index();
            $t->string('email', 190)->index();
            $t->string('role_slug', 100);
            $t->string('token_hash', 64)->unique();
            $t->timestampTz('expires_at');
            $t->timestampTz('accepted_at')->nullable();
            $t->unsignedBigInteger('invited_by')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        DB::statement("ALTER TABLE institutes ADD CONSTRAINT institutes_status_check CHECK (status IN ('pending','active','suspended','closed'))");
    }

    public function down(): void
    {
        foreach (['institute_invites', 'institute_users', 'institutes'] as $x) Schema::dropIfExists($x);
    }
};