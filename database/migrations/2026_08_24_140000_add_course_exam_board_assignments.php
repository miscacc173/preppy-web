<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_exam_boards', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_id')->index();
            $table->unsignedBigInteger('exam_board_id')->index();
            $table->timestampsTz();
            $table->unique(['course_id', 'exam_board_id']);
        });

        // Preserve every existing single-board assignment. Legacy courses that
        // represented a qualification without a board are made available to
        // the currently active boards so deployment does not hide live data.
        DB::statement(<<<'SQL'
            INSERT INTO course_exam_boards (course_id, exam_board_id, created_at, updated_at)
            SELECT courses.id, exam_boards.id, NOW(), NOW()
            FROM courses
            JOIN exam_boards ON exam_boards.id = courses.exam_board_id
            ON CONFLICT (course_id, exam_board_id) DO NOTHING
        SQL);
        DB::statement(<<<'SQL'
            INSERT INTO course_exam_boards (course_id, exam_board_id, created_at, updated_at)
            SELECT courses.id, exam_boards.id, NOW(), NOW()
            FROM courses
            CROSS JOIN exam_boards
            WHERE courses.exam_board_id IS NULL
              AND exam_boards.status = 'active'
            ON CONFLICT (course_id, exam_board_id) DO NOTHING
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('course_exam_boards');
    }
};
