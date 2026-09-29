<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class PricingService
{
    public function priceFor(Plan $plan, ?int $instituteId = null): array
    {
        $price = $plan->price_minor;
        if ($instituteId) {
            $custom = DB::table('institute_plan_prices')->where(['institute_id' => $instituteId, 'plan_id' => $plan->id, 'is_active' => true])->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->orderByDesc('starts_at')->first();
            if ($custom) $price = $custom->price_minor;
        }
        return ['price_minor' => (int)$price, 'currency' => $plan->currency];
    }
}
