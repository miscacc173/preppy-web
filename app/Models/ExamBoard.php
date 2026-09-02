<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamBoard extends Model
{
    protected $table = 'exam_boards';
    protected $fillable = ['name', 'code', 'status', 'is_active', 'metadata'];

    protected static function booted(): void
    {
        static::saving(function (ExamBoard $board): void {
            if ($board->isDirty('status')) $board->is_active = $board->status !== 'inactive';
            elseif ($board->isDirty('is_active')) $board->status = $board->is_active ? 'active' : 'inactive';
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'metadata' => 'array'];
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_exam_boards')->withTimestamps();
    }
}
