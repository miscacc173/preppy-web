<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducatorAccessGrant extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['can_manage_parents' => 'boolean', 'revoked_at' => 'datetime']; }
    public function educator() { return $this->belongsTo(User::class, 'educator_user_id'); }
    public function institute() { return $this->belongsTo(Institute::class); }
}
