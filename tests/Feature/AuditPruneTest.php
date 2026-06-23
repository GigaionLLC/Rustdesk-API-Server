<?php

namespace Tests\Feature;

use App\Models\AuditConn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Audit retention: the `audit:prune` command deletes rows older than the window and is a no-op
 * when retention is disabled.
 */
class AuditPruneTest extends TestCase
{
    use RefreshDatabase;

    private function conn(string $peer, int $ageDays): AuditConn
    {
        $row = AuditConn::create(['action' => 'new', 'conn_id' => 1, 'peer_id' => $peer, 'ip' => '10.0.0.1']);
        $row->forceFill(['created_at' => now()->subDays($ageDays)])->save();

        return $row;
    }

    public function test_prune_removes_rows_older_than_the_window(): void
    {
        $old = $this->conn('old', 100);
        $recent = $this->conn('recent', 5);

        Artisan::call('audit:prune', ['--days' => 30]);

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_prune_is_a_noop_when_disabled(): void
    {
        config(['rustdesk.audit_retention_days' => 0]);
        $old = $this->conn('old', 100);

        Artisan::call('audit:prune'); // uses config (0 = keep forever)

        $this->assertModelExists($old);
    }

    public function test_prune_uses_config_window(): void
    {
        config(['rustdesk.audit_retention_days' => 30]);
        $old = $this->conn('old', 100);

        Artisan::call('audit:prune');

        $this->assertModelMissing($old);
    }
}
