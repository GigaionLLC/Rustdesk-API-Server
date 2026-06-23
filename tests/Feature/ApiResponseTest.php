<?php

namespace Tests\Feature;

use App\Models\Strategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the response-shape contract the RustDesk client depends on:
 * success acks must be JSON objects ({}), while list endpoints stay arrays ([]).
 * Also guards that strategy options survive a save (the key/value array round-trip).
 */
class ApiResponseTest extends TestCase
{
    use RefreshDatabase;

    private function clientToken(): string
    {
        User::create([
            'username' => 'cli', 'password' => 'secret12345', 'status' => User::STATUS_NORMAL,
        ]);

        return $this->postJson('/api/login', [
            'username' => 'cli', 'password' => 'secret12345', 'id' => 'd1', 'uuid' => 'u1',
        ])->json('access_token');
    }

    public function test_address_book_ack_is_an_object_not_an_array(): void
    {
        $res = $this->withHeader('Authorization', 'Bearer '.$this->clientToken())
            ->postJson('/api/ab/peer/add/personal', ['id' => '900', 'alias' => 'PC']);

        $res->assertOk();
        // The client parses this as a JSON map — must be "{}", never "[]".
        $this->assertSame('{}', $res->getContent());
    }

    public function test_login_options_stays_an_array(): void
    {
        $res = $this->getJson('/api/login-options');
        $res->assertOk();
        $this->assertSame('[]', $res->getContent()); // list endpoint: empty array, not "{}"
    }

    public function test_audit_endpoints_return_objects(): void
    {
        $this->assertSame('{}', $this->postJson('/api/audit/conn', [
            'id' => 'p1', 'action' => 'new', 'conn_id' => 1,
        ])->getContent());
        $this->assertSame('{}', $this->postJson('/api/audit/alarm', [
            'id' => 'p1', 'typ' => 0, 'info' => 'x',
        ])->getContent());
    }

    public function test_deploy_returns_a_json_object_with_a_result_field(): void
    {
        // The client JSON-parses this and reads `result`; a bare text body breaks it.
        $res = $this->postJson('/api/devices/deploy', ['id' => 'x', 'uuid' => 'u', 'pk' => 'p']);

        $res->assertOk()->assertJsonStructure(['result']);
        $this->assertIsString($res->json('result'));
    }

    public function test_peer_force_always_relay_is_serialised_as_a_string(): void
    {
        $token = $this->clientToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/ab/peer/add/personal', ['id' => '555', 'forceAlwaysRelay' => 'true'])
            ->assertOk();

        $res = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/ab/peers');
        $res->assertOk();

        $peer = collect($res->json('data'))->firstWhere('id', '555');
        // Client does `json['forceAlwaysRelay'] == 'true'` — must be the string, not a bool.
        $this->assertSame('true', $peer['forceAlwaysRelay']);
    }

    public function test_audit_active_guid_then_note_roundtrip(): void
    {
        $token = $this->clientToken();

        // Controlled host opens a connection (unauthenticated audit ingest).
        $this->postJson('/api/audit/conn', [
            'id' => 'host1', 'action' => 'new', 'conn_id' => 7, 'session_id' => 'sess-9',
        ])->assertOk();

        // Controlling client fetches the live session's guid (bare JSON string).
        $guid = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/audit/conn/active?id=host1&session_id=sess-9&conn_type=0')
            ->assertOk()
            ->json();
        $this->assertIsString($guid);
        $this->assertNotEmpty($guid);

        // ...then attaches an end-of-connection note keyed on that guid.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/audit', ['guid' => $guid, 'note' => 'handled ticket #42'])
            ->assertOk();

        $this->assertDatabaseHas('audit_conns', ['guid' => $guid, 'note' => 'handled ticket #42']);
    }

    public function test_strategy_update_preserves_options(): void
    {
        $admin = User::create([
            'username' => 'admin', 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);
        $strategy = Strategy::create(['name' => 'P', 'enabled' => true, 'options' => [], 'modified_at' => 1]);

        // Mirrors what the fixed live-save sends: parallel option_keys / option_values arrays.
        $this->actingAs($admin)->put(route('admin.strategies.update', $strategy), [
            'name' => 'P',
            'enabled' => 1,
            'option_keys' => ['enable-audio', 'enable-clipboard'],
            'option_values' => ['N', 'Y'],
        ])->assertOk();

        $strategy->refresh();
        $this->assertSame(['enable-audio' => 'N', 'enable-clipboard' => 'Y'], $strategy->options);
    }
}
