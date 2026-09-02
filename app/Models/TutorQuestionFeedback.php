<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorQuestionFeedback extends Model
{
    protected $table = 'tutor_question_feedback';
    protected $fillable = ['tutor_user_id', 'session_type', 'session_id', 'question_key', 'question_text', 'answer_text', 'feedback', 'verdict', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
