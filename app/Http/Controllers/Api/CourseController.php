<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;

class CourseController extends CrudController
{
    protected string $model = Course::class;
    protected array $searchable = ['name', 'code', 'level'];
    protected array $with = ['examBoards', 'subjects'];
    protected array $storeRules = ['exam_board_id' => 'nullable|exists:exam_boards,id', 'board_ids' => 'required|array|min:1', 'board_ids.*' => 'integer|exists:exam_boards,id', 'name' => 'required|string|max:150', 'code' => 'required|string|max:80', 'level' => 'required|string|max:40', 'qualification_family' => 'required|string|max:40', 'start_year' => 'nullable|integer', 'end_year' => 'nullable|integer', 'status' => 'sometimes|in:active,coming_soon,enabled,inactive', 'is_active' => 'boolean', 'metadata' => 'array', 'subject_ids' => 'required|array|min:1', 'subject_ids.*' => 'integer|exists:subjects,id'];
    protected array $updateRules = [];

    public function store(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate($this->storeRules);
        $ids = $data['subject_ids'] ?? [];
        $boardIds = $data['board_ids'] ?? [];
        unset($data['subject_ids'], $data['board_ids']);
        $data['exam_board_id'] = $boardIds[0] ?? null;
        $m = ($this->model)::create($data);
        $m->subjects()->sync($ids);
        $m->examBoards()->sync($boardIds);
        return response()->json(['data' => $m->load($this->with)], 201);
    }

    public function update(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $m = ($this->model)::findOrFail($id);
        $data = $request->validate($this->storeRules);
        $ids = $data['subject_ids'] ?? null;
        $boardIds = $data['board_ids'] ?? null;
        unset($data['subject_ids'], $data['board_ids']);
        $data['exam_board_id'] = $boardIds[0] ?? null;
        $m->update($data);
        if ($ids !== null) $m->subjects()->sync($ids);
        if ($boardIds !== null) $m->examBoards()->sync($boardIds);
        return response()->json(['data' => $m->fresh()->load($this->with)]);
    }
}
