<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartGuestDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_grant' => ['required', 'string', 'size:64'],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('status', 'active')],
            'exam_board_id' => ['required', 'integer', Rule::exists('exam_boards', 'id')->where('status', 'active')],
            'subject_ids' => ['required', 'array', 'size:1'],
            'subject_ids.*' => ['integer', Rule::exists('subjects', 'id')->where('status', 'active')],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
