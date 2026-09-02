<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_boards', function (Blueprint $table): void {
            $table->string('status', 30)->default('active')->index();
        });
        Schema::table('courses', function (Blueprint $table): void {
            $table->string('status', 30)->default('active')->index();
        });

        DB::table('exam_boards')->where('is_active', false)->update(['status' => 'inactive']);
        DB::table('courses')->where('is_active', false)->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('exam_boards', fn (Blueprint $table) => $table->dropColumn('status'));
    }
};
