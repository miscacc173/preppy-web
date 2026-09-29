<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanCourseSubject extends Model
{
    protected $fillable = ['plan_id', 'course_id', 'subject_id'];

    public function plan() { return $this->belongsTo(Plan::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
