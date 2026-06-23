<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harden API keys: record the last IP that used a key, and allow restricting a key to a list
 * of source IPs (exact match, comma-separated; empty = any).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            $table->string('last_used_ip')->nullable()->after('last_used_at');
            $table->string('allowed_ips')->nullable()->after('scopes');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table): void {
            $table->dropColumn(['last_used_ip', 'allowed_ips']);
        });
    }
};
