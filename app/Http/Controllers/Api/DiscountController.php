<?php

namespace App\Http\Controllers\Api;

use App\Models\Discount;

class DiscountController extends CrudController
{
    protected string $model = Discount::class;
    protected array $searchable = ['name', 'code'];
    protected array $storeRules = ['name' => 'required|string|max:150', 'code' => 'nullable|string|max:80|unique:discounts,code', 'discount_type' => 'required|in:fixed,percentage', 'amount_minor' => 'nullable|integer|min:0', 'percentage' => 'nullable|numeric|min:0|max:100', 'institute_id' => 'nullable|exists:institutes,id', 'plan_id' => 'nullable|exists:plans,id', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'max_redemptions' => 'nullable|integer|min:1', 'is_active' => 'boolean', 'rules' => 'array'];
    protected array $updateRules = ['name' => 'required|string|max:150', 'code' => 'nullable|string|max:80|unique:discounts,code', 'discount_type' => 'required|in:fixed,percentage', 'amount_minor' => 'nullable|integer|min:0', 'percentage' => 'nullable|numeric|min:0|max:100', 'institute_id' => 'nullable|exists:institutes,id', 'plan_id' => 'nullable|exists:plans,id', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'max_redemptions' => 'nullable|integer|min:1', 'is_active' => 'boolean', 'rules' => 'array'];
}
