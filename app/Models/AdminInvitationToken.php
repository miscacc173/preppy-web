<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminInvitationToken extends Model
{
    protected $fillable = ['invitation_type', 'invitation_id', 'encrypted_token'];
}
