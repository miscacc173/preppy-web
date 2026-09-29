<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = ['subject_id', 'name', 'code', 'status', 'display_order', 'metadata'];

    protected function casts(): array
    {
        return ['display_order' => 'integer', 'metadata' => 'array'];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
