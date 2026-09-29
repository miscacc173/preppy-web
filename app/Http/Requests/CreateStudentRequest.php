<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:150', 'email' => 'required|email|max:190|unique:users,email', 'password' => 'required|string|min:8', 'course_id' => ['required', 'integer', Rule::exists('courses', 'id')->where('status', 'active')], 'exam_board_id' => ['required', 'integer', Rule::exists('exam_boards', 'id')->where('status', 'active')], 'school_year' => 'nullable|string|max:40', 'school_name' => 'nullable|string|max:180', 'subject_ids' => 'required|array|min:1', 'subject_ids.*' => ['integer', Rule::exists('subjects', 'id')->where('status', 'active')]];
    }
}
