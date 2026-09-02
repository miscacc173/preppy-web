<?php

namespace App\Http\Controllers\Api;

use App\Models\InstitutePlanPrice;

class InstitutePlanPriceController extends CrudController
{
    protected string $model = InstitutePlanPrice::class;
    protected array $searchable = [];
    protected array $storeRules = ['institute_id' => 'required|exists:institutes,id', 'plan_id' => 'required|exists:plans,id', 'price_minor' => 'required|integer|min:0', 'currency' => 'required|string|size:3', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'boolean', 'metadata' => 'array'];
    protected array $updateRules = ['institute_id' => 'required|exists:institutes,id', 'plan_id' => 'required|exists:plans,id', 'price_minor' => 'required|integer|min:0', 'currency' => 'required|string|size:3', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'boolean', 'metadata' => 'array'];
}
