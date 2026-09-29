<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subject_id')->index();
            $table->string('name', 180);
            $table->string('code', 100);
            $table->string('status', 30)->default('active')->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();
            $table->unique(['subject_id', 'code']);
        });

        Schema::create('tutor_question_feedback', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tutor_user_id')->index();
            $table->string('session_type', 20)->index();
            $table->string('session_id', 100)->index();
            $table->string('question_key', 190);
            $table->text('question_text');
            $table->text('answer_text')->nullable();
            $table->text('feedback');
            $table->string('verdict', 30)->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();
            $table->unique(['tutor_user_id', 'session_type', 'session_id', 'question_key'], 'tutor_feedback_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_question_feedback');
        Schema::dropIfExists('topics');
    }
};
