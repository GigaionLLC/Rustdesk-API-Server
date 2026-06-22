<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_conns', function (Blueprint $table) {
            // Operator session note posted by the controlling side ({id, session_id, note}).
            $table->text('note')->nullable()->after('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('audit_conns', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
