<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guest_demo_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('email', 190)->nullable()->index();
            $t->string('device_fingerprint', 190)->nullable()->index();
            $t->string('status', 30)->default('active')->index();
            $t->timestampTz('started_at');
            $t->timestampTz('expires_at');
            $t->timestampTz('converted_at')->nullable();
            $t->unsignedBigInteger('converted_user_id')->nullable()->index();
            $t->jsonb('progress_snapshot')->default(DB::raw("'{}'::jsonb"));
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('student_profiles', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->unique();
            $t->string('ownership_type', 30)->index();
            $t->unsignedBigInteger('institute_id')->nullable()->index();
            $t->unsignedBigInteger('course_id')->nullable()->index();
            $t->unsignedBigInteger('exam_board_id')->nullable()->index();
            $t->string('school_year', 40)->nullable();
            $t->string('school_name', 180)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('status', 30)->default('active')->index();
            $t->timestampTz('onboarded_at')->nullable();
            $t->jsonb('preferences')->default(DB::raw("'{}'::jsonb"));
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->softDeletesTz();
        });
        Schema::create('student_subjects', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('student_profile_id')->index();
            $t->unsignedBigInteger('subject_id')->index();
            $t->string('status', 30)->default('active')->index();
            $t->timestampTz('started_at')->nullable();
            $t->timestampTz('ended_at')->nullable();
            $t->timestampsTz();
            $t->unique(['student_profile_id', 'subject_id']);
        });
        Schema::create('parent_student_links', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('parent_user_id')->index();
            $t->unsignedBigInteger('student_user_id')->index();
            $t->string('relationship', 60)->nullable();
            $t->string('status', 30)->default('pending')->index();
            $t->boolean('can_view_performance')->default(true);
            $t->boolean('can_view_subscription')->default(false);
            $t->unsignedBigInteger('invited_by')->nullable();
            $t->timestampTz('accepted_at')->nullable();
            $t->timestampsTz();
            $t->unique(['parent_user_id', 'student_user_id']);
        });
        DB::statement("ALTER TABLE student_profiles ADD CONSTRAINT student_ownership_check CHECK ((ownership_type='platform' AND institute_id IS NULL) OR (ownership_type='institute' AND institute_id IS NOT NULL))");
        DB::statement("ALTER TABLE student_profiles ADD CONSTRAINT student_ownership_value_check CHECK (ownership_type IN ('platform','institute'))");
        DB::statement("CREATE INDEX student_profiles_institute_active_idx ON student_profiles(institute_id) WHERE deleted_at IS NULL AND ownership_type='institute'");
    }

    public function down(): void
    {
        foreach (['parent_student_links', 'student_subjects', 'student_profiles', 'guest_demo_sessions'] as $x) Schema::dropIfExists($x);
    }
};