<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional per-book peer cap. NULL = fall back to the server-wide RUSTDESK_AB_MAX_PEERS;
 * 0 = unlimited; >0 = that cap for this book specifically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('address_books', function (Blueprint $table): void {
            $table->unsignedInteger('max_peers')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('address_books', function (Blueprint $table): void {
            $table->dropColumn('max_peers');
        });
    }
};
