<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreppyPlanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $oLevel = $this->course('CAIE_O_LEVEL_2026');
        $igcse = $this->course('CAIE_IGCSE_2026');
        $physics = $this->subject('5054');
        $chemistry = $this->subject('5070');
        $biology = $this->subject('5090');
        $math = $this->subject('4024');

        $plans = [
            ['Physics Monthly', 'PHYSICS_MONTHLY', 'subject', 'month', 149900, true, [[$oLevel, [$physics]], [$igcse, [$physics]]]],
            ['Physics Quarterly', 'PHYSICS_QUARTERLY', 'subject', 'quarter', 399900, true, [[$oLevel, [$physics]], [$igcse, [$physics]]]],
            ['Physics Yearly', 'PHYSICS_YEARLY', 'subject', 'year', 1399900, true, [[$oLevel, [$physics]], [$igcse, [$physics]]]],
            ['Science Duo', 'SCIENCE_DUO', 'multi_subject', 'month', 249900, true, [[$oLevel, [$physics,$chemistry]], [$igcse, [$physics,$biology]]]],
            ['O Level Core', 'OLEVEL_CORE', 'course', 'quarter', 1099900, true, [[$oLevel, [$physics,$math]]]],
            ['O Level Complete', 'OLEVEL_COMPLETE', 'course', 'month', 499900, true, [[$oLevel, [$physics,$chemistry,$biology,$math]]]],
            ['Multi Course Premium', 'MULTI_COURSE_PREMIUM', 'multi_subject', 'quarter', 1649900, true, [[$oLevel, [$physics,$chemistry,$math]], [$igcse, [$physics,$biology,$math]]]],
            ['All Subjects Premium', 'ALL_SUBJECTS_PREMIUM', 'all_subjects', 'year', 6999900, true, [[$oLevel, [$physics,$chemistry,$biology,$math]], [$igcse, [$physics,$chemistry,$biology,$math]]]],
            ['Institute Campus Plan', 'INSTITUTE_CAMPUS', 'institute', 'month', 299900, false, [[$oLevel, [$physics,$math,$chemistry]]]],
        ];

        foreach ($plans as [$name, $code, $scope, $interval, $price, $isPublic, $courseSelections]) {
            $primaryCourseId = count($courseSelections) === 1 ? $courseSelections[0][0] : null;
            DB::table('plans')->updateOrInsert(['code' => $code], [
                'name' => $name, 'scope' => $scope, 'course_id' => $primaryCourseId,
                'billing_interval' => $interval, 'billing_interval_count' => 1,
                'price_minor' => $price, 'currency' => 'PKR', 'trial_days' => 0,
                'is_public' => $isPublic, 'is_active' => true,
                'features' => json_encode(['student_access' => true, 'seeded_demo' => true]),
                'metadata' => json_encode(['seeded' => true, 'platform' => 'Preppy', 'course_first' => true]),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $planId = DB::table('plans')->where('code', $code)->value('id');
            DB::table('plan_course_subjects')->where('plan_id', $planId)->delete();
            DB::table('plan_subjects')->where('plan_id', $planId)->delete();
            $allSubjects = [];
            foreach ($courseSelections as [$courseId, $subjects]) {
                foreach ($subjects as $subjectId) {
                    DB::table('plan_course_subjects')->insertOrIgnore(['plan_id' => $planId, 'course_id' => $courseId, 'subject_id' => $subjectId, 'created_at' => $now, 'updated_at' => $now]);
                    $allSubjects[$subjectId] = true;
                }
            }
            foreach (array_keys($allSubjects) as $subjectId) {
                DB::table('plan_subjects')->insertOrIgnore(['plan_id' => $planId, 'subject_id' => $subjectId]);
            }
        }

        $instituteId = DB::table('institutes')->where('code', 'PREPPY-DEMO-001')->value('id');
        $campusPlanId = $this->plan('INSTITUTE_CAMPUS');
        DB::table('institute_plan_prices')->updateOrInsert(
            ['institute_id' => $instituteId, 'plan_id' => $campusPlanId, 'starts_at' => null],
            ['price_minor' => 249900, 'currency' => 'PKR', 'ends_at' => null, 'is_active' => true, 'metadata' => json_encode(['seeded' => true, 'negotiated' => true]), 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('discounts')->updateOrInsert(['code' => 'WELCOME20'], [
            'name' => 'Preppy Welcome 20%', 'discount_type' => 'percentage', 'amount_minor' => null,
            'percentage' => 20.0000, 'institute_id' => null, 'plan_id' => null,
            'starts_at' => $now->copy()->subDay(), 'ends_at' => $now->copy()->addMonths(3),
            'max_redemptions' => 1000, 'redemptions_count' => 1, 'is_active' => true,
            'rules' => json_encode(['first_subscription_only' => true, 'seeded' => true]),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('discounts')->updateOrInsert(['code' => 'PREPPY500'], [
            'name' => 'Preppy PKR 500 Off', 'discount_type' => 'fixed', 'amount_minor' => 50000,
            'percentage' => null, 'institute_id' => null, 'plan_id' => null,
            'starts_at' => $now->copy()->subDay(), 'ends_at' => $now->copy()->addMonths(6),
            'max_redemptions' => 500, 'redemptions_count' => 0, 'is_active' => true,
            'rules' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('institute_commission_rules')->updateOrInsert(
            ['institute_id' => $instituteId, 'plan_id' => $campusPlanId, 'commission_type' => 'percentage'],
            ['direction' => 'platform_pays_institute', 'fixed_amount_minor' => null, 'percentage' => 15.0000, 'currency' => 'PKR', 'starts_at' => $now->copy()->subMonth(), 'ends_at' => null, 'is_active' => true, 'metadata' => json_encode(['seeded' => true]), 'created_at' => $now, 'updated_at' => $now]
        );
    }

    private function course(string $code): int { return (int) DB::table('courses')->where('code', $code)->value('id'); }
    private function subject(string $code): int { return (int) DB::table('subjects')->where('code', $code)->where('syllabus_year_start', 2026)->value('id'); }
    private function plan(string $code): int { return (int) DB::table('plans')->where('code', $code)->value('id'); }
}
