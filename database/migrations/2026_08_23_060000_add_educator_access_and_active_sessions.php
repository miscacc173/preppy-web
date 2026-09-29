<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('educator_invites', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('email', 190)->index();
            $table->string('role_slug', 20);
            $table->unsignedBigInteger('institute_id')->nullable()->index();
            $table->string('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->unsignedBigInteger('invited_by')->index();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();
        });
        Schema::create('teaching_classes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('institute_id')->index();
            $table->string('name', 180);
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('teacher_user_id')->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestampsTz();
        });
        Schema::create('teaching_class_students', function (Blueprint $table): void {
            $table->unsignedBigInteger('teaching_class_id');
            $table->unsignedBigInteger('student_user_id');
            $table->timestampsTz();
            $table->primary(['teaching_class_id', 'student_user_id']);
        });
        Schema::create('educator_access_grants', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('educator_user_id')->index();
            $table->unsignedBigInteger('institute_id')->nullable()->index();
            $table->string('scope_type', 20)->index();
            $table->unsignedBigInteger('scope_id')->index();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedBigInteger('granted_by');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
            $table->unique(['educator_user_id', 'scope_type', 'scope_id'], 'educator_scope_unique');
        });
        Schema::table('learning_sessions', function (Blueprint $table): void {
            $table->string('status', 20)->default('completed')->index();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable()->change();
        });
        DB::statement("ALTER TABLE educator_invites ADD CONSTRAINT educator_invites_role_check CHECK (role_slug IN ('teacher','tutor'))");
        DB::statement("ALTER TABLE educator_access_grants ADD CONSTRAINT educator_scope_type_check CHECK (scope_type IN ('class','course','student','subject'))");
        DB::statement("ALTER TABLE learning_sessions ADD CONSTRAINT learning_sessions_status_check CHECK (status IN ('active','completed','abandoned'))");
    }

    public function down(): void
    {
        DB::table('learning_sessions')->whereNull('completed_at')->delete();
        Schema::table('learning_sessions', function (Blueprint $table): void {
            $table->dropColumn(['status', 'started_at']);
            $table->timestampTz('completed_at')->nullable(false)->change();
        });
        Schema::dropIfExists('educator_access_grants');
        Schema::dropIfExists('teaching_class_students');
        Schema::dropIfExists('teaching_classes');
        Schema::dropIfExists('educator_invites');
    }
};
