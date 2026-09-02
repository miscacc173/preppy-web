<?php

namespace App\Http\Controllers\Api;

use App\Models\InstituteCommissionRule;

class CommissionRuleController extends CrudController
{
    protected string $model = InstituteCommissionRule::class;
    protected array $searchable = [];
    protected array $storeRules = ['institute_id' => 'required|exists:institutes,id', 'plan_id' => 'nullable|exists:plans,id', 'commission_type' => 'required|in:fixed,percentage', 'direction' => 'required|in:platform_pays_institute,platform_collects_from_institute', 'fixed_amount_minor' => 'nullable|integer|min:0', 'percentage' => 'nullable|numeric|min:0|max:100', 'currency' => 'required|string|size:3', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'boolean', 'metadata' => 'array'];
    protected array $updateRules = ['institute_id' => 'required|exists:institutes,id', 'plan_id' => 'nullable|exists:plans,id', 'commission_type' => 'required|in:fixed,percentage', 'direction' => 'required|in:platform_pays_institute,platform_collects_from_institute', 'fixed_amount_minor' => 'nullable|integer|min:0', 'percentage' => 'nullable|numeric|min:0|max:100', 'currency' => 'required|string|size:3', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'boolean', 'metadata' => 'array'];
}
