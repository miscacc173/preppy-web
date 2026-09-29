<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_course_subjects', function (Blueprint $table): void {
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('subject_id');
            $table->timestampsTz();
            $table->primary(['plan_id', 'course_id', 'subject_id']);
            $table->index(['plan_id', 'course_id']);
            $table->index(['course_id', 'subject_id']);
        });

        // Backfill legacy plans so existing plans continue to work after the
        // course-first plan editor is introduced. Only relationships that can
        // be inferred safely are copied.
        if (Schema::hasTable('plans') && Schema::hasTable('plan_subjects')) {
            $plans = DB::table('plans')->select(['id', 'course_id'])->get();
            foreach ($plans as $plan) {
                $subjectIds = DB::table('plan_subjects')->where('plan_id', $plan->id)->pluck('subject_id');
                if ($plan->course_id && $subjectIds->isNotEmpty()) {
                    foreach ($subjectIds as $subjectId) {
                        if (DB::table('course_subjects')->where(['course_id' => $plan->course_id, 'subject_id' => $subjectId])->exists()) {
                            DB::table('plan_course_subjects')->insertOrIgnore([
                                'plan_id' => $plan->id,
                                'course_id' => $plan->course_id,
                                'subject_id' => $subjectId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_course_subjects');
    }
};
