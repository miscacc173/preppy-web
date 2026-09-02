<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 150);
            $t->string('email', 190)->unique();
            $t->string('password')->nullable();
            $t->string('status', 30)->default('active')->index();
            $t->timestampTz('email_verified_at')->nullable();
            $t->timestampTz('last_login_at')->nullable();
            $t->string('timezone', 64)->default('UTC');
            $t->string('locale', 12)->default('en');
            $t->rememberToken();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->softDeletesTz();
        });
        Schema::create('social_accounts', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->index();
            $t->string('provider', 40);
            $t->string('provider_user_id', 190);
            $t->string('provider_email', 190)->nullable();
            $t->text('avatar_url')->nullable();
            $t->text('access_token')->nullable();
            $t->text('refresh_token')->nullable();
            $t->timestampTz('token_expires_at')->nullable();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->unique(['provider', 'provider_user_id']);
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 100);
            $t->string('slug', 100)->unique();
            $t->string('scope', 30)->default('system');
            $t->boolean('is_system')->default(true);
            $t->timestampsTz();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 120);
            $t->string('slug', 120)->unique();
            $t->string('group', 80)->nullable()->index();
            $t->timestampsTz();
        });
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::create('user_roles', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('role_id')->index();
            $t->string('context_type', 40)->default('system');
            $t->unsignedBigInteger('context_id')->nullable()->index();
            $t->timestampTz('starts_at')->nullable();
            $t->timestampTz('ends_at')->nullable();
            $t->unsignedBigInteger('assigned_by')->nullable();
            $t->timestampsTz();
            $t->unique(['user_id', 'role_id', 'context_type', 'context_id'], 'user_role_context_unique');
        });
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('pending','active','suspended','disabled'))");
    }

    public function down(): void
    {
        foreach (['user_roles', 'role_permissions', 'permissions', 'roles', 'social_accounts', 'users'] as $x) Schema::dropIfExists($x);
    }
};