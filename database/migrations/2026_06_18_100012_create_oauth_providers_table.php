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
        Schema::create('oauth_providers', function (Blueprint $table) {
            $table->id();
            $table->string('op')->index();
            $table->string('type');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('scopes')->nullable();
            $table->string('issuer')->nullable();
            $table->boolean('auto_register')->default(false);
            $table->boolean('pkce_enable')->default(false);
            $table->string('pkce_method')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_providers');
    }
};
