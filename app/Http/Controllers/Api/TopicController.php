<?php

namespace App\Http\Controllers\Api;

use App\Models\Topic;

class TopicController extends CrudController
{
    protected string $model = Topic::class;
    protected array $searchable = ['name', 'code'];
    protected array $with = ['subject'];
    protected array $storeRules = [
        'subject_id' => 'required|exists:subjects,id',
        'name' => 'required|string|max:180',
        'code' => 'required|string|max:100',
        'status' => 'sometimes|in:active,coming_soon,enabled,inactive',
        'display_order' => 'sometimes|integer|min:0',
        'metadata' => 'array',
    ];
    protected array $updateRules = [
        'subject_id' => 'required|exists:subjects,id',
        'name' => 'required|string|max:180',
        'code' => 'required|string|max:100',
        'status' => 'sometimes|in:active,coming_soon,enabled,inactive',
        'display_order' => 'sometimes|integer|min:0',
        'metadata' => 'array',
    ];
}
