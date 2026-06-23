<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The end-of-connection note feature needs a stable handle the controlling client can
 * reference. On a new connection the client queries GET /api/audit/conn/active to fetch a
 * server-issued guid for the live session, caches it, then PUT /api/audit {guid, note} to
 * attach an operator note. See docs/modernization/16-response-contract.md §0.5 / row 28.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_conns', function (Blueprint $table): void {
            $table->uuid('guid')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_conns', function (Blueprint $table): void {
            $table->dropColumn('guid');
        });
    }
};
