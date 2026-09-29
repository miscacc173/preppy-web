<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_app_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('key', 120);
            $table->jsonb('value');
            $table->timestampsTz();
            $table->unique(['user_id', 'key']);
        });
    }
    public function down(): void { Schema::dropIfExists('user_app_states'); }
};
