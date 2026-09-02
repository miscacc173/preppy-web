<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppleIapProduct extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean', 'metadata' => 'array']; }
    public function plan() { return $this->belongsTo(Plan::class); }
}
