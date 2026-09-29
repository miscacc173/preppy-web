<?php

namespace App\Models;

use App\Enums\PlanScope;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'code', 'scope', 'course_id', 'billing_interval', 'billing_interval_count', 'price_minor', 'currency', 'trial_days', 'is_public', 'is_active', 'features', 'metadata'];

    protected function casts(): array
    {
        return ['scope' => PlanScope::class, 'is_public' => 'boolean', 'is_active' => 'boolean', 'features' => 'array', 'metadata' => 'array'];
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'plan_subjects');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseSubjectSelections()
    {
        return $this->hasMany(PlanCourseSubject::class);
    }
}
