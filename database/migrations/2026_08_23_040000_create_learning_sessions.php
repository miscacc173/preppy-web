<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('learning_sessions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('session_uid', 100)->unique();
            $table->string('session_type', 20)->index();
            $table->string('level', 80)->nullable();
            $table->string('subject', 180);
            $table->string('topic', 240)->nullable();
            $table->unsignedInteger('earned')->default(0);
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedTinyInteger('accuracy')->default(0);
            $table->string('grade', 20)->nullable();
            $table->unsignedInteger('time_seconds')->default(0);
            $table->unsignedInteger('average_seconds_per_question')->default(0);
            $table->jsonb('questions')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('completed_at')->index();
            $table->timestampsTz();
        });
        DB::statement("ALTER TABLE learning_sessions ADD CONSTRAINT learning_sessions_type_check CHECK (session_type IN ('learn','exam'))");
        DB::statement('ALTER TABLE learning_sessions ADD CONSTRAINT learning_sessions_accuracy_check CHECK (accuracy BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_sessions');
    }
};
