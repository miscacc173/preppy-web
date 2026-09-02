<?php

namespace Database\Seeders;

use App\Models\AppleIapProduct;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class AppleIapProductSeeder extends Seeder
{
    public function run(): void
    {
        // Configure actual App Store Connect product IDs here or seed them from your deployment tooling.
        // Example (kept disabled intentionally):
        // $plan = Plan::where('code', 'MONTHLY')->first();
        // if ($plan) AppleIapProduct::updateOrCreate(['product_id' => 'com.preppy.monthly'], ['plan_id' => $plan->id, 'is_active' => true]);
    }
}
