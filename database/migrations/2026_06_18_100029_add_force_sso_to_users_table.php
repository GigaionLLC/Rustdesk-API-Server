<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SSO-only login policy: when set, the local-password path is rejected (LDAP/OIDC
            // stay allowed). Default false keeps existing accounts using passwords as before.
            $table->boolean('force_sso')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('force_sso');
        });
    }
};
