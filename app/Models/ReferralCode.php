<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    protected $fillable = ['user_id', 'code', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
