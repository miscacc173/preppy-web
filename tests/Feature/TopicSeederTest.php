<?php

namespace Tests\Feature;

use Database\Seeders\TestingAcademicDataSeeder;
use Database\Seeders\TopicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TopicSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_topics_for_every_seeded_subject_without_duplicates(): void
    {
        $this->seed(TestingAcademicDataSeeder::class);
        $this->seed(TopicSeeder::class);

        $subjects = DB::table('subjects')->whereIn('code', ['5054', '5070', '5090', '4024', '2210', '1123'])->get();
        $this->assertCount(6, $subjects);

        foreach ($subjects as $subject) {
            $this->assertGreaterThan(0, DB::table('topics')->where('subject_id', $subject->id)->count());
        }

        $firstCount = DB::table('topics')->count();
        $this->seed(TopicSeeder::class);

        $this->assertSame($firstCount, DB::table('topics')->count());
        $this->assertSame(
            DB::table('topics')->count(),
            DB::table('topics')->select(['subject_id', 'code'])->distinct()->get()->count(),
        );
    }
}
