<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Admin Role Layer 3 (docs/modernization/12-access-control-design.md): scoped, delegated
     * console permissions. A role grants a set of capability strings; users may hold several
     * (union of perms). type = global|individual|group; scope holds group ids for group type.
     */
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('type')->default('global');
            // Group ids (and similar) for scoped roles.
            $table->json('scope')->nullable();
            // Capability strings, e.g. ["devices.view", "users.edit"].
            $table->json('perms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
