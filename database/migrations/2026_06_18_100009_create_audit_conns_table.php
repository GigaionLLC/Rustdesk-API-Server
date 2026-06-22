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
        Schema::create('audit_conns', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->bigInteger('conn_id')->index();
            $table->string('peer_id')->index();
            $table->string('from_peer')->nullable();
            $table->string('from_name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('session_id')->nullable();
            $table->integer('type')->default(0);
            $table->string('uuid')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_conns');
    }
};
