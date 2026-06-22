<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verify_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            // 1 = email, 2 = totp.
            $table->integer('type');
            $table->string('uuid')->index();
            $table->string('code')->nullable();
            $table->string('rustdesk_id')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verify_codes');
    }
};
