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
        Schema::create('address_book_peers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('address_book_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('rustdesk_id')->index();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('hostname')->nullable();
            $table->string('alias')->nullable();
            $table->string('platform')->nullable();
            $table->json('tags')->nullable();
            $table->string('hash')->nullable();
            $table->boolean('force_always_relay')->default(false);
            $table->string('rdp_port')->nullable();
            $table->string('rdp_username')->nullable();
            $table->string('login_name')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address_book_peers');
    }
};
