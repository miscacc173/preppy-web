<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['questions' => 'array', 'metadata' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
