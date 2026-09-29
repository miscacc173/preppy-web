<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentStudentLink extends Model
{
    protected $fillable = ['parent_user_id', 'student_user_id', 'relationship', 'status', 'can_view_performance', 'can_view_subscription', 'invited_by', 'accepted_at', 'invite_token_hash', 'invite_expires_at'];

    protected function casts(): array
    {
        return ['can_view_performance' => 'boolean', 'can_view_subscription' => 'boolean', 'accepted_at' => 'datetime', 'invite_expires_at' => 'datetime'];
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }
}
