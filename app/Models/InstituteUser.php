<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteUser extends Model
{
    protected $fillable = ['institute_id', 'user_id', 'position', 'status', 'invited_by', 'joined_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
