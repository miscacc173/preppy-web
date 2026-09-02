<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_invitation_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('invitation_type', 30);
            $table->unsignedBigInteger('invitation_id');
            $table->text('encrypted_token');
            $table->timestampsTz();
            $table->unique(['invitation_type', 'invitation_id'], 'admin_invitation_token_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_invitation_tokens');
    }
};
