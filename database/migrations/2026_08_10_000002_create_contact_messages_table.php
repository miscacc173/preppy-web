<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('kind', 30)->default('contact');
            $table->string('name', 100);
            $table->string('email', 190);
            $table->string('subject', 180);
            $table->string('category', 80)->nullable();
            $table->text('message');
            $table->string('status', 30)->default('open')->index();
            $table->timestampsTz();
        });
    }
    public function down(): void { Schema::dropIfExists('contact_messages'); }
};
