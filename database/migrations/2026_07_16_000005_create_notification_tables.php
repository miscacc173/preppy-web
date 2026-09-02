<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->index();
            $t->string('notification_type', 80);
            $t->string('channel', 30);
            $t->boolean('is_enabled')->default(true);
            $t->jsonb('schedule')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->unique(['user_id', 'notification_type', 'channel'], 'notification_preference_unique');
        });
        Schema::create('user_devices', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->index();
            $t->string('platform', 30);
            $t->string('device_id', 190);
            $t->text('push_token')->nullable();
            $t->string('timezone', 64)->nullable();
            $t->timestampTz('last_seen_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $t->timestampsTz();
            $t->unique(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('notification_preferences');
    }
};