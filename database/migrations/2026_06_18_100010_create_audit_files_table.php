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
        Schema::create('audit_files', function (Blueprint $table) {
            $table->id();
            $table->string('peer_id')->index();
            $table->string('from_peer')->nullable();
            $table->string('from_name')->nullable();
            $table->text('info')->nullable();
            $table->boolean('is_file')->default(true);
            $table->string('path')->nullable();
            $table->integer('type')->default(0);
            $table->string('ip', 45)->nullable();
            $table->integer('num')->default(0);
            $table->string('uuid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_files');
    }
};
