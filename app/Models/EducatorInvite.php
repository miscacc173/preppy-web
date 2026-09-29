<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducatorInvite extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'metadata' => 'array']; }
    public function institute() { return $this->belongsTo(Institute::class); }
}
