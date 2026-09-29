<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['user_id', 'kind', 'name', 'email', 'subject', 'category', 'message', 'status'];
}
