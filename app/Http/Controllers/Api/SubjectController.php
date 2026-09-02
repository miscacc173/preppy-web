<?php

namespace App\Http\Controllers\Api;

use App\Models\Subject;

class SubjectController extends CrudController
{
    protected string $model = Subject::class;
    protected array $searchable = ['name', 'code'];
    protected array $storeRules = ['name' => 'required|string|max:150', 'code' => 'required|string|max:80', 'syllabus_year_start' => 'nullable|integer', 'syllabus_year_end' => 'nullable|integer', 'status' => 'sometimes|in:active,coming_soon,enabled,inactive', 'metadata' => 'array'];
    protected array $updateRules = ['name' => 'required|string|max:150', 'code' => 'required|string|max:80', 'syllabus_year_start' => 'nullable|integer', 'syllabus_year_end' => 'nullable|integer', 'status' => 'sometimes|in:active,coming_soon,enabled,inactive', 'metadata' => 'array'];
}
