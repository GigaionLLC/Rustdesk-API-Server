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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('rustdesk_id')->unique();
            $table->string('uuid')->index();
            $table->string('cpu')->nullable();
            $table->string('hostname')->nullable();
            $table->string('memory')->nullable();
            $table->string('os')->nullable();
            $table->string('username')->nullable();
            $table->string('version')->nullable();
            $table->string('alias')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->unsignedBigInteger('device_group_id')->nullable()->index();
            $table->unsignedBigInteger('strategy_id')->nullable()->index();
            $table->boolean('is_online')->default(false);
            $table->integer('conns')->default(0);
            $table->timestamp('last_online_at')->nullable();
            $table->string('last_online_ip', 45)->nullable();
            $table->string('device_username')->nullable();
            $table->string('device_name')->nullable();
            $table->string('note')->nullable();
            $table->boolean('approved')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
