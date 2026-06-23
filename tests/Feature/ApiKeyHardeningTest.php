<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API-key hardening: source-IP allowlist, last-used-IP recording, and secret rotation.
 */
class ApiKeyHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: string, 2: ApiKey}
     */
    private function makeKey(?string $allowedIps): array
    {
        $user = User::create([
            'username' => 'op'.uniqid(), 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);
        [$plain, $prefix, $hash] = ApiKey::generateSecret();
        $key = ApiKey::create([
            'user_id' => $user->id, 'name' => 'k', 'token_hash' => $hash,
            'prefix' => $prefix, 'scopes' => ['devices.read'], 'allowed_ips' => $allowedIps,
        ]);

        return [$user, $plain, $key];
    }

    public function test_request_from_a_disallowed_ip_is_rejected(): void
    {
        [, $plain] = $this->makeKey('203.0.113.7');

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
            ->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/devices')
            ->assertStatus(403);
    }

    public function test_request_from_an_allowed_ip_succeeds_and_records_it(): void
    {
        [, $plain, $key] = $this->makeKey('203.0.113.7');
        Device::create(['rustdesk_id' => 'd1', 'uuid' => 'u1']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/devices')
            ->assertOk();

        $this->assertSame('203.0.113.7', $key->refresh()->last_used_ip);
    }

    public function test_no_allowlist_permits_any_ip(): void
    {
        [, $plain] = $this->makeKey(null);

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/devices')
            ->assertOk();
    }

    public function test_rotate_invalidates_the_old_secret(): void
    {
        $admin = User::create([
            'username' => 'admin', 'password' => 'secret12345', 'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);
        [, $plain, $key] = $this->makeKey(null);
        $oldHash = $key->token_hash;

        $this->actingAs($admin)
            ->post(route('admin.api-keys.rotate', $key))
            ->assertSessionHas('new_api_key');

        $this->assertNotSame($oldHash, $key->refresh()->token_hash);

        // Old secret no longer authenticates.
        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/devices')
            ->assertStatus(401);
    }
}
