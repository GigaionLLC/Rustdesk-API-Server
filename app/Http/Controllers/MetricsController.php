<?php

namespace App\Http\Controllers;

use App\Models\AddressBookPeer;
use App\Models\Alarm;
use App\Models\Device;
use App\Models\Strategy;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Prometheus metrics exposition (GET /metrics). Disabled (404) until RUSTDESK_METRICS_TOKEN is
 * set; once set, scrapers must present it as `Authorization: Bearer <token>`.
 */
class MetricsController extends Controller
{
    public function index(Request $request): Response
    {
        $token = (string) config('rustdesk.metrics_token', '');

        // No token configured ⇒ the endpoint is off and indistinguishable from "not found".
        if ($token === '') {
            abort(404);
        }

        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response('Unauthorized', 401);
        }

        /** @var array<string, array{help: string, value: int}> $metrics */
        $metrics = [
            'rustdesk_devices_total' => ['help' => 'Total registered devices', 'value' => Device::count()],
            'rustdesk_devices_online' => ['help' => 'Devices currently online', 'value' => Device::where('is_online', true)->count()],
            'rustdesk_users_total' => ['help' => 'Total user accounts', 'value' => User::count()],
            'rustdesk_strategies_total' => ['help' => 'Configured strategies', 'value' => Strategy::count()],
            'rustdesk_alarms_total' => ['help' => 'Recorded alarms', 'value' => Alarm::count()],
            'rustdesk_address_book_peers_total' => ['help' => 'Address-book peers', 'value' => AddressBookPeer::count()],
            'rustdesk_webhook_deliveries_failed' => ['help' => 'Webhook deliveries currently failed', 'value' => WebhookDelivery::where('status', WebhookDelivery::STATUS_FAILED)->count()],
        ];

        $lines = ['# HELP rustdesk_up Whether the API is responding', '# TYPE rustdesk_up gauge', 'rustdesk_up 1'];
        foreach ($metrics as $name => $meta) {
            $lines[] = "# HELP {$name} {$meta['help']}";
            $lines[] = "# TYPE {$name} gauge";
            $lines[] = "{$name} {$meta['value']}";
        }

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }
}
