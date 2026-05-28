<?php

namespace App\Jobs\Site;

use App\Models\Server;
use App\Models\ServerLog;
use App\Services\LogAnalysis\GoAccess\GoAccess;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupSiteStatsJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 120;

    public function __construct(protected Server $server, protected int $siteId)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        if ($this->siteId <= 0) {
            return;
        }

        $this->run("server-{$this->server->id}-site-{$this->siteId}-stats", function (): void {
            $base = GoAccess::BASE_DIR;
            $this->server->ssh()->exec(
                'sudo rm -rf '.escapeshellarg("{$base}/data/{$this->siteId}").' '.escapeshellarg("{$base}/sites/{$this->siteId}.conf"),
                'cleanup-site-stats'
            );
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log(
            $this->server,
            'cleanup-site-stats-failed',
            $e->getMessage()
        );
    }
}
