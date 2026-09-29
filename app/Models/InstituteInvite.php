<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteInvite extends Model
{
    protected $table = 'institute_invites';
    protected $fillable = ['institute_id', 'email', 'role_slug', 'token_hash', 'expires_at', 'accepted_at', 'invited_by', 'metadata'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'metadata' => 'array'];
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
