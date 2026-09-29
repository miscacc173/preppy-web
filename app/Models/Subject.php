<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'code', 'syllabus_year_start', 'syllabus_year_end', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_subjects')->withPivot(['is_compulsory', 'display_order'])->withTimestamps();
    }

    public function topics()
    {
        return $this->hasMany(Topic::class)->orderBy('display_order')->orderBy('name');
    }
}
