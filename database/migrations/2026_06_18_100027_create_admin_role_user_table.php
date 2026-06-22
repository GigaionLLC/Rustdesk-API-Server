<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot joining users to their admin roles (Admin Role Layer 3).
     */
    public function up(): void
    {
        Schema::create('admin_role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_role_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();

            $table->unique(['admin_role_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_role_user');
    }
};
