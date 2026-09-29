<?php

namespace App\Models;

use App\Enums\StudentOwnershipType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static findOrFail(mixed $student_profile_id)
 */
class StudentProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'ownership_type',
        'institute_id',
        'course_id',
        'exam_board_id',
        'school_year',
        'school_name',
        'date_of_birth',
        'status',
        'onboarded_at',
        'preferences',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['ownership_type' => StudentOwnershipType::class, 'date_of_birth' => 'date', 'onboarded_at' => 'datetime', 'preferences' => 'array', 'metadata' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function examBoard()
    {
        return $this->belongsTo(ExamBoard::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')->withPivot(['status', 'started_at', 'ended_at'])->withTimestamps();
    }
}
