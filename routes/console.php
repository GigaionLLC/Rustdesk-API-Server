<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Re-drive failed webhook deliveries (exponential backoff) and prune old history. Requires the
// scheduler cron (`* * * * * php artisan schedule:run`); harmless if not run — manual "Resend"
// from the console still works.
Schedule::command('webhooks:retry')->everyFiveMinutes()->withoutOverlapping();

// Enforce the audit retention window (no-op unless RUSTDESK_AUDIT_RETENTION_DAYS > 0).
Schedule::command('audit:prune')->daily();
