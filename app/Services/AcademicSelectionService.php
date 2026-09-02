<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AcademicSelectionService
{
    /**
     * Validate the dependent Course -> Board -> Subjects selection against
     * Admin-managed catalog relationships and return normalized IDs.
     */
    public function validate(array $data, bool $requireSubjects = true): array
    {
        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : 0;
        $boardId = isset($data['exam_board_id']) ? (int) $data['exam_board_id'] : 0;
        $subjectIds = collect(Arr::wrap($data['subject_ids'] ?? []))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $errors = [];
        if (! $courseId) $errors['course_id'] = ['Select a course.'];
        if (! $boardId) $errors['exam_board_id'] = ['Select an exam board available for the selected course.'];
        if ($requireSubjects && $subjectIds->isEmpty()) $errors['subject_ids'] = ['Select at least one subject available for the selected course.'];

        $course = $courseId ? Course::query()->where('status', 'active')->with(['examBoards', 'subjects'])->find($courseId) : null;
        if ($courseId && ! $course) $errors['course_id'] = ['The selected course is not available.'];

        if ($course && $boardId && ! $course->examBoards->contains(fn ($board) => $board->id === $boardId && $board->status === 'active')) {
            $errors['exam_board_id'] = ['The selected exam board is not available for this course.'];
        }

        if ($course && $subjectIds->isNotEmpty()) {
            $allowed = $course->subjects->where('status', 'active')->pluck('id');
            $invalid = $subjectIds->diff($allowed);
            if ($invalid->isNotEmpty()) $errors['subject_ids'] = ['One or more selected subjects are not available for this course.'];
        }

        if ($errors) throw ValidationException::withMessages($errors);

        return ['course_id' => $courseId, 'exam_board_id' => $boardId, 'subject_ids' => $subjectIds->all()];
    }
}
