<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_boards', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 150);
            $t->string('code', 40)->unique();
            $t->boolean('is_active')->default(true);
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
        });
        Schema::create('courses', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('exam_board_id')->nullable()->index();
            $t->string('name', 150);
            $t->string('code', 80);
            $t->string('level', 40)->index();
            $t->string('qualification_family', 40)->index();
            $t->smallInteger('start_year')->nullable();
            $t->smallInteger('end_year')->nullable();
            $t->boolean('is_active')->default(true);
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->unique(['exam_board_id', 'code', 'start_year'], 'course_board_code_year_unique');
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 150);
            $t->string('code', 80);
            $t->smallInteger('syllabus_year_start')->nullable();
            $t->smallInteger('syllabus_year_end')->nullable();
            $t->string('status', 30)->default('active')->index();
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->unique(['code', 'syllabus_year_start', 'syllabus_year_end'], 'subject_code_year_unique');
        });
        Schema::create('course_subjects', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('course_id')->index();
            $t->unsignedBigInteger('subject_id')->index();
            $t->boolean('is_compulsory')->default(false);
            $t->unsignedInteger('display_order')->default(0);
            $t->timestampsTz();
            $t->unique(['course_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['course_subjects', 'subjects', 'courses', 'exam_boards'] as $x) Schema::dropIfExists($x);
    }
};