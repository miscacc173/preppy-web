<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->where('trial_days', '>', 0)->update(['trial_days' => 0]);
    }

    public function down(): void
    {
        // Trial values are intentionally not restored. The product no longer offers trials.
    }
};
