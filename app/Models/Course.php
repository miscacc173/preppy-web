<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Course extends Model
{
    protected $fillable = ['exam_board_id', 'name', 'code', 'level', 'qualification_family', 'start_year', 'end_year', 'status', 'is_active', 'metadata'];

    protected static function booted(): void
    {
        static::saving(function (Course $course): void {
            if ($course->isDirty('status')) $course->is_active = $course->status !== 'inactive';
            elseif ($course->isDirty('is_active')) $course->status = $course->is_active ? 'active' : 'inactive';
        });
        static::saved(function (Course $course): void {
            if ($course->exam_board_id && Schema::hasTable('course_exam_boards')) {
                $course->examBoards()->syncWithoutDetaching([$course->exam_board_id]);
            }
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'metadata' => 'array'];
    }

    public function examBoard()
    {
        return $this->belongsTo(ExamBoard::class);
    }

    public function examBoards()
    {
        return $this->belongsToMany(ExamBoard::class, 'course_exam_boards')->withTimestamps();
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'course_subjects')->withPivot(['is_compulsory', 'display_order'])->withTimestamps();
    }
}
