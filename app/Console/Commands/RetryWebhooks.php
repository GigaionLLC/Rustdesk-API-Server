<?php

namespace App\Console\Commands;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Console\Command;

/**
 * Re-drive failed webhook deliveries that are due for retry, then prune old delivery rows.
 * Schedule it (every few minutes) so transient endpoint failures recover without a queue worker:
 *   php artisan webhooks:retry
 */
class RetryWebhooks extends Command
{
    protected $signature = 'webhooks:retry {--prune-days=14 : Delete delivery rows older than this}';

    protected $description = 'Retry due failed webhook deliveries and prune old delivery history';

    public function handle(WebhookService $webhooks): int
    {
        $due = WebhookDelivery::with('webhook')
            ->where('status', WebhookDelivery::STATUS_FAILED)
            ->where('attempts', '<', WebhookDelivery::MAX_ATTEMPTS)
            ->whereNotNull('next_attempt_at')
            ->where('next_attempt_at', '<=', now())
            ->orderBy('next_attempt_at')
            ->limit(200)
            ->get();

        $retried = 0;
        foreach ($due as $delivery) {
            if ($delivery->webhook !== null && $delivery->webhook->enabled) {
                $webhooks->attempt($delivery->webhook, $delivery);
                $retried++;
            }
        }

        $pruneDays = max(1, (int) $this->option('prune-days'));
        $pruned = WebhookDelivery::where('created_at', '<', now()->subDays($pruneDays))->delete();

        $this->info("Retried {$retried} webhook deliveries; pruned {$pruned} old rows.");

        return self::SUCCESS;
    }
}
