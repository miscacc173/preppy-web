<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingClass extends Model
{
    protected $guarded = [];
    public function students() { return $this->belongsToMany(User::class, 'teaching_class_students', 'teaching_class_id', 'student_user_id')->withTimestamps(); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_user_id'); }
    public function course() { return $this->belongsTo(Course::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
