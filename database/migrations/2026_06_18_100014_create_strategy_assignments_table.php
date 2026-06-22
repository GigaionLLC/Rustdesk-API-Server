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
        Schema::create('strategy_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('strategy_id')->index();
            // 'device' | 'user' | 'device_group'.
            $table->string('target_type');
            $table->bigInteger('target_id')->index();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategy_assignments');
    }
};
