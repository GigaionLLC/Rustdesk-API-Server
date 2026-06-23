<?php

namespace Tests\Feature;

use App\Models\Strategy;
use App\Models\User;
use App\Services\ClientConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Client Config generator: the encoded server-config string must round-trip exactly the
 * way the RustDesk client decodes it (reverse → url-safe base64 → JSON {host,relay,api,key}).
 */
class ClientConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_string_round_trips_like_the_client(): void
    {
        $svc = new ClientConfigService;
        $cs = $svc->configString('h.example.com', 'r.example.com', 'https://api.example.com', 'KEY123');

        // Exactly what the client does in ServerConfig.decode / custom_server.rs.
        $json = json_decode(base64_decode(strtr(strrev($cs), '-_', '+/')), true);

        $this->assertSame('h.example.com', $json['host']);
        $this->assertSame('r.example.com', $json['relay']);
        $this->assertSame('https://api.example.com', $json['api']);
        $this->assertSame('KEY123', $json['key']);
    }

    public function test_qr_payload_is_prefixed_with_config(): void
    {
        $this->assertStringStartsWith('config=', (new ClientConfigService)->qrPayload('h', 'r', 'a', 'k'));
    }

    public function test_installer_filename_matches_the_client_parser(): void
    {
        $this->assertSame(
            'rustdesk-host=h,key=k,api=a,relay=r.exe',
            (new ClientConfigService)->installerFilename('h', 'r', 'a', 'k'),
        );
    }

    public function test_page_renders_an_svg_qr_for_an_admin(): void
    {
        $admin = User::create([
            'username' => 'admin', 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.client-config.index', ['host' => 'h.example.com', 'key' => 'K']))
            ->assertOk()
            ->assertSee('Config string')
            ->assertSee('<svg', false);
    }

    public function test_unlock_pin_renders_per_os_set_commands(): void
    {
        $admin = User::create([
            'username' => 'admin', 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.client-config.index', ['unlock_pin' => '4821']))
            ->assertOk()
            ->assertSee('--set-unlock-pin 4821')
            ->assertSee('cannot', false); // explains it can't be pushed by a strategy
    }

    public function test_no_unlock_pin_card_without_a_pin(): void
    {
        $admin = User::create([
            'username' => 'admin', 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.client-config.index'))
            ->assertOk()
            ->assertDontSee('--set-unlock-pin');
    }

    public function test_install_script_renders_option_commands_per_os(): void
    {
        $svc = new ClientConfigService;
        $scripts = $svc->installScript([
            'direct-server' => 'Y',
            'direct-access-port' => '21118',
            'enable-clipboard' => '',          // empty → skipped
            'whitelist' => '10.0.0.1 10.0.0.2', // spaces → quoted
        ], '2084502424');

        $this->assertStringContainsString('sudo rustdesk --set-unlock-pin 2084502424', $scripts['Linux']);
        $this->assertStringContainsString('sudo rustdesk --option direct-server Y', $scripts['Linux']);
        $this->assertStringContainsString('--option direct-access-port 21118', $scripts['Linux']);
        $this->assertStringNotContainsString('enable-clipboard', $scripts['Linux']); // empty skipped
        $this->assertStringContainsString('--option whitelist "10.0.0.1 10.0.0.2"', $scripts['Linux']);
        $this->assertStringContainsString('rustdesk.exe', $scripts['Windows']);
    }

    public function test_strategy_install_script_renders_for_admin(): void
    {
        $admin = User::create([
            'username' => 'admin', 'password' => 'secret12345',
            'is_admin' => true, 'status' => User::STATUS_NORMAL,
        ]);
        $strategy = Strategy::create([
            'name' => 'Locked', 'enabled' => true,
            'options' => ['enable-tunnel' => 'Y', 'enable-lan-discovery' => 'N'], 'modified_at' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.client-config.index', ['strategy' => $strategy->id, 'unlock_pin' => '4821']))
            ->assertOk()
            ->assertSee('Install script')
            ->assertSee('--option enable-tunnel Y')
            ->assertSee('--option enable-lan-discovery N')
            ->assertSee('--set-unlock-pin 4821');
    }
}
