<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Access Control Layer 1 (docs/modernization/12-access-control-design.md): a directional
     * grant. Members of `group_id` may access devices owned by users in `can_access_group_id`.
     */
    public function up(): void
    {
        Schema::create('user_group_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->index();
            $table->unsignedBigInteger('can_access_group_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_group_access');
    }
};
