<?php

namespace App\Http\Controllers\Api;

use App\Models\ExamBoard;

class ExamBoardController extends CrudController
{
    protected string $model = ExamBoard::class;
    protected array $searchable = ['name', 'code'];
    protected array $storeRules = ['name' => 'required|string|max:150', 'code' => 'required|string|max:40|unique:exam_boards,code', 'status' => 'sometimes|in:active,coming_soon,enabled,inactive', 'is_active' => 'boolean', 'metadata' => 'array'];
    protected array $updateRules = ['name' => 'required|string|max:150', 'code' => 'required|string|max:40|unique:exam_boards,code', 'status' => 'sometimes|in:active,coming_soon,enabled,inactive', 'is_active' => 'boolean', 'metadata' => 'array'];
}
