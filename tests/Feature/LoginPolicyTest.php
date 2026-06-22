<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the unverified-status gating and the SSO-only login policy on the client API
 * (docs/modernization/15-deepscan-synthesis.md). Backward compatible: a NORMAL account with
 * force_sso=false still logs in with a local password (see SmokeTest).
 */
class LoginPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_login(): void
    {
        User::create([
            'username' => 'unverified', 'password' => 'secret12345',
            'is_admin' => false, 'status' => User::STATUS_UNVERIFIED,
        ]);

        $this->postJson('/api/login', [
            'username' => 'unverified', 'password' => 'secret12345',
            'id' => 'dev-u', 'uuid' => 'uuid-dev-u',
        ])->assertOk()->assertExactJson(['error' => 'Account not verified']);
    }

    public function test_disabled_user_cannot_login(): void
    {
        User::create([
            'username' => 'disabled', 'password' => 'secret12345',
            'is_admin' => false, 'status' => User::STATUS_DISABLED,
        ]);

        $this->postJson('/api/login', [
            'username' => 'disabled', 'password' => 'secret12345',
            'id' => 'dev-d', 'uuid' => 'uuid-dev-d',
        ])->assertOk()->assertExactJson(['error' => 'Account disabled']);
    }

    public function test_force_sso_user_cannot_use_password(): void
    {
        User::create([
            'username' => 'ssoonly', 'password' => 'secret12345',
            'is_admin' => false, 'status' => User::STATUS_NORMAL, 'force_sso' => true,
        ]);

        $this->postJson('/api/login', [
            'username' => 'ssoonly', 'password' => 'secret12345',
            'id' => 'dev-s', 'uuid' => 'uuid-dev-s',
        ])->assertOk()->assertExactJson(['error' => 'This account must sign in via SSO']);
    }
}
