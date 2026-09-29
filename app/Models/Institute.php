<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institute extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'code', 'status', 'billing_email', 'phone', 'address', 'city', 'country_code', 'timezone', 'created_by', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'institute_users')->withPivot(['position', 'status', 'joined_at'])->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(StudentProfile::class);
    }
}
