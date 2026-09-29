<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $table = 'social_accounts';
    protected $fillable = ['user_id', 'provider', 'provider_user_id', 'provider_email', 'avatar_url', 'access_token', 'refresh_token', 'token_expires_at', 'metadata'];

    protected function casts(): array
    {
        return ['token_expires_at' => 'datetime', 'metadata' => 'array'];
    }
}
